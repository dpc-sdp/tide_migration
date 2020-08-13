<?php

namespace Drupal\tide_migration\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;

/**
 * Obtain JSON data for migration using the JSON API.
 *
 * @DataParser(
 *   id = "tide_data_parser_json",
 *   title = @Translation("Tide Data Parser JSON")
 * )
 */
class TideDataParserJson extends Json {

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl($url) {
    // (Re)open the provided URL.
    $source_data = $this->getSourceData($url);
    $this->iterator = new \ArrayIterator($source_data);
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  protected function getSourceData($url) {
    $source_data = $this->getDataFetcherPlugin()->getResponseContent($url);
    $selectors = explode('/', trim($this->itemSelector, '/'));
    foreach ($selectors as $selector) {
      if (!empty($selector)) {
        $source_data = $source_data[$selector];
      }
    }
    return $source_data;
  }

}
