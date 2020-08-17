<?php
namespace Drupal\tide_migration\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\Yaml\Yaml;

class ConfigFetch {

  const FILENAME = 'tide_migration.settings.yml';

  /** @var ModuleHandlerInterface */
  private $moduleHandler;

  /**
   * ConfigFetch constructor.
   * @param ModuleHandlerInterface $moduleHandler
   */
  public function __construct(ModuleHandlerInterface $moduleHandler)
  {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * @param $key
   * @return mixed|null
   */
  public function fetchValue($key) {
    $value = NULL;
    $config = \Drupal::config('tide_migration.settings');
    return $config->get($key);
  }
}
