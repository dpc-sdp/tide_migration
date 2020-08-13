<?php

namespace Drupal\tide_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\DataFetcherPluginBase;
use Drupal\migrate_plus\Plugin\migrate\source\Url;
use Drupal\tide_migration\Service\UrlFilterBuilder;

/**
 * Source plugin for retrieving event content from json endpoint.
 *
 * @MigrateSource(
 *   id = "tide_source_events"
 * )
 */
class TideSourceEvents extends Url {

  /**
   * The source URLs to retrieve.
   *
   * @var array
   */
  protected $sourceUrls = [];

  /**
   * The data parser plugin.
   *
   * @var \Drupal\migrate_plus\DataParserPluginInterface
   */
  protected $dataParserPlugin;

  /**
   * @var UrlFilterBuilder
   */
  private $urlFilterBuilder;

  /**
   * @var DataFetcherPluginBase
   */
  private $dataFetcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    $this->dataFetcher = \Drupal::service('plugin.manager.migrate_plus.data_fetcher')->createInstance('http', []);
    $this->urlFilterBuilder = new UrlFilterBuilder($this->dataFetcher);

    $urls = $this->urlFilterBuilder->generateUrls($configuration);

    $configuration['urls'] = $urls;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->sourceUrls = $urls;
  }

  /**
   * Return a string representing the source URLs.
   *
   * @return string
   *   Comma-separated list of URLs being imported.
   */
  public function __toString() {
    // This could cause a problem when using a lot of urls, may need to hash.
    $urls = implode(', ', $this->sourceUrls);
    return $urls;
  }

  /**
   * Returns the initialized data parser plugin.
   *
   * @return \Drupal\migrate_plus\DataParserPluginInterface
   *   The data parser plugin.
   */
  public function getDataParserPlugin() {
    if (!isset($this->dataParserPlugin)) {
      $this->dataParserPlugin = \Drupal::service('plugin.manager.migrate_plus.data_parser')->createInstance($this->configuration['data_parser_plugin'], $this->configuration);
    }
    return $this->dataParserPlugin;
  }

  /**
   * Creates and returns a filtered Iterator over the documents.
   *
   * @return \Iterator
   *   An iterator over the documents providing source rows that match the
   *   configured item_selector.
   */
  protected function initializeIterator() {
    return $this->getDataParserPlugin();
  }
}
