<?php
namespace Drupal\tide_migration\Service;

use Drupal\migrate_plus\DataFetcherPluginBase;
use Drupal\tide_migration\Enum\ReservedConfigNameEnum;
use GuzzleHttp\Stream\StreamInterface;

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

  public function __construct($dataFetcher) {
    $this->reservedConfigNameEnum = new ReservedConfigNameEnum();
    $this->configFetch = new ConfigFetch();
    $this->dataFetcher = $dataFetcher;
  }

  public function generateOffsetUrls($url, $output) {
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

  private function recursiveFilterChange(&$filters) {
    foreach ($filters as $key => $value) {
      if (is_array($value)) {
        $this->recursiveFilterChange($value);
      } else {
        if (strpos($value, '@') !== FALSE) {
          if ($this->reservedConfigNameEnum->validate(ltrim($value, '@')) === TRUE) {
            $filters[$key] = $this->configFetch->fetchValue(ltrim($value, '@'));
          }
        }
      }
    }
  }

  /**
   * Build source url with filters and fields to include.
   *
   * @param array $configuration
   */
  public function buildInitialUrlFilters(array $configuration) {
    $urls = [];

    foreach ($configuration['urls'] as $url) {
      $filters = [];

      if (!empty($url['filters'])) {
        $data = $url['filters'];
        $this->recursiveFilterChange($data);
        $filters['filter'] = $data;
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

    return $urls;
  }

  /**
   * Build source url with filters and fields to include.
   */
  public function generateUrls(array $configuration) {
    $urls = [];

    $initial_event_urls = $this->buildInitialUrlFilters($configuration);

    foreach ($initial_event_urls as $url) {
      $urls = array_merge($this->buildEventUrlOffsets($url), $urls);
    }

    return $urls;
  }

  public function buildEventUrlOffsets(string $url) {
    /** @var StreamInterface $stream */
    $stream = $this->dataFetcher->getResponseContent($url);

    $source_data = json_decode($stream->getContents(), TRUE);

    if (!empty($source_data['links']) && !empty($source_data['links']['last']) && !empty($source_data['links']['last']['href'])) {
      $related_link = $source_data['links']['last']['href'];

      $parsed_url = parse_url($related_link);

      parse_str($parsed_url['query'], $output);

      return $this->generateOffsetUrls($url, $output);
    }

    return [$url];
  }

}
