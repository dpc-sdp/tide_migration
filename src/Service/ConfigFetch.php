<?php

namespace Drupal\tide_migration\Service;

class ConfigFetch
{
  /** @var \Drupal\Core\Config\ImmutableConfig  */
  private $config;

  /**
   * ConfigFetch constructor.
   */
  public function __construct()
  {
    $this->config = \Drupal::config('tide_migration.settings');
  }

  /**
   * @param $key
   * @return array|mixed|null
   */
  public function fetchValue($key)
  {
    return $this->config->get($key);
  }
}
