<?php
namespace Drupal\tide_migration\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_plus\DataFetcherPluginBase;
use Drupal\migrate_plus\DataFetcherPluginInterface;
use Drupal\tide_migration\Enum\ReservedConfigNameEnum;
use Psr\Http\Message\StreamInterface;

class UrlFilterBuilder {

  /** @var ReservedConfigNameEnum */
  private $reservedConfigNameEnum;

  /**
   * @var DataFetcherPluginBase
   */
  private $dataFetcher;

  /**
   * @var ConfigFetch
   */
  private $configFetch;

  /**
   * UrlFilterBuilder constructor.
   * @param DataFetcherPluginInterface $dataFetcher
   * @param ModuleHandlerInterface $moduleHandler
   */
  public function __construct(DataFetcherPluginInterface $dataFetcher, ModuleHandlerInterface $moduleHandler) {
    $this->reservedConfigNameEnum = new ReservedConfigNameEnum();
    $this->configFetch = new ConfigFetch($moduleHandler);
    $this->dataFetcher = $dataFetcher;
  }

  /**
   * @param $filters
   * @return mixed
   */
  private function recursiveFilterChange(&$filters) {
    foreach ($filters as $key => $value) {
      if (is_array($value)) {
        $filters[$key] = $this->recursiveFilterChange($value);
      } else {
        if (strpos($value, '@') !== FALSE) {
          if ($this->reservedConfigNameEnum->validate(ltrim($value, '@')) === TRUE) {
            $filters[$key] = $this->configFetch->fetchValue(ltrim($value, '@'));
          }
        }
      }
    }

    return $filters;
  }

  /**
   * @param array $configuration
   * @return array
   */
  public function buildInitialUrlFilters(array $configuration) {
    $urls = [];

    if (!empty($configuration['urls'])) {
      foreach ($configuration['urls'] as $url) {
        $filters = [];

        if (!empty($url['filters'])) {
          $data = $url['filters'];
          $filters['filter'] = $this->recursiveFilterChange($data);
        }

        if (!empty($url['include'])) {
          $filters['include'] = implode(',', $url['include']);
        }

        if (!empty($url['page_filter'])) {
          $filters['page'] = $url['page_filter'];
        }

        if (!empty($configuration['site']) && is_numeric($configuration['site'])) {
          $filters['site'] = $configuration['site'];

          if (strpos($configuration['site'], '@') !== FALSE) {
            if ($this->reservedConfigNameEnum->validate(ltrim($configuration['site'], '@')) === TRUE) {
              $filters['site'] = $this->configFetch->fetchValue(ltrim($configuration['site'], '@'));
            }
          }
        }

        $urls[] = $url['url'] . '?' .http_build_query($filters);
      }
    }

    return $urls;
  }

  /**
   * Build source url with filters and fields to include.
   *
   * @param array $configuration
   * @return array
   */
  public function generateUrls(array $configuration) {
    $urls = [];

    $initial_urls = $this->buildInitialUrlFilters($configuration);

    foreach ($initial_urls as $url) {
      $urls = array_merge($this->buildUrlOffsets($url), $urls);
    }

    return $urls;
  }

  /**
   * @param string $url
   * @return array|string[]
   */
  private function buildUrlOffsets(string $url) {
    /** @var StreamInterface $stream */
    $stream = $this->dataFetcher->getResponseContent($url);

    $source_data = json_decode($stream->getContents(), TRUE);

    if (!empty($source_data['links']) && !empty($source_data['links']['last']) && !empty($source_data['links']['last']['href'])) {
      $related_link = $source_data['links']['last']['href'];

      $parsed_url = parse_url($related_link);

      if ($parsed_url !== FALSE) {
        if (!empty($parsed_url['query'])) {
          parse_str($parsed_url['query'], $output);
          $offset_urls = $this->generateOffsetUrls($url, $output);

          if (!empty($offset_urls)) {
            return $offset_urls;
          }
        }
      }
    }

    return [$url];
  }

  /**
   * Build incremental urls based on the last url present in the json data.
   *
   * @param string $url
   * @param array $output
   * @return array|null
   */
  private function generateOffsetUrls(string $url, array $output) {
    if (!isset($output['page']['offset']) && !isset($output['page']['limit'])) {
      return NULL;
    }

    $last_offset = $output['page']['offset'];
    $limit = $output['page']['limit'];
    $page_count = $last_offset / $limit;
    $urls = [];
    $start_offset = 0;

    $url_without_params = strtok($url, '?');

    for ($i = 0; $i <= $page_count; $i++) {
      $output_clone = $output;
      $output_clone['page']['offset'] = $start_offset;

      $start_offset += $limit;

      $urls[] = $url_without_params . '?' . http_build_query($output_clone);
    }

    return $urls;
  }

}
