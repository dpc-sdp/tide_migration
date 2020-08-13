<?php

namespace Drupal\tide_migration\Plugin\migrate_plus\data_fetcher;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\Http;
use Drupal\tide_migration\Mapper\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends the HTTP data fetcher with cache.
 *
 * All options of the default HTTP fetcher are available.
 * New option:
 *  - cache_lifetime: in seconds, 0 for no cache, default to 21600.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: url
 *   data_fetcher_plugin: tide_event_cache
 *   cache_lifetime: 21600
 * @endcode
 *
 * @DataFetcher(
 *   id = "tide_event_cache",
 *   title = @Translation("Tide events with cache")
 * )
 */
class TideEventCache extends Http {

  /**
   * Cache life time.
   *
   * @var int
   */
  protected $cacheLifetime = 21600;

  /**
   * Migrate cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Events mapper.
   *
   * @var Events
   */
  protected $eventsMapper;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CacheBackendInterface $cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cache = $cache;
    $this->cacheLifetime = $configuration['cache_lifetime'] ?? $this->cacheLifetime;
    $this->eventsMapper = new Events();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.migrate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseContent($url) {
    $cid = 'tide_migration:tide_migration_events_cache:url:' . hash('sha256', $url);

    $cached_response = $this->cache->get($cid);
    if (!empty($cached_response)) {
      return $cached_response->data;
    }

    $response = parent::getResponseContent($url);
    $json_response = json_decode($response->getContents(), TRUE);

    $content = $this->eventsMapper->convert($json_response);

    if ($content) {
      try {
        $this->cache->set($cid, $content, ($this->cacheLifetime >= 0) ? (\Drupal::time()->getRequestTime() + $this->cacheLifetime) : Cache::PERMANENT);
      }
      catch (\Exception $exception) {
        watchdog_exception('tide_migration', $exception, NULL, [], RfcLogLevel::INFO);
      }
    }

    return $content;
  }
}
