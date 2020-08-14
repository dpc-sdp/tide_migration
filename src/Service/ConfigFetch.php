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
    $module_path = $this->moduleHandler->getModule('tide_migration')->getPath();

    if (file_exists($module_path . '/config/' . $this::FILENAME)) {
      $file_contents = file_get_contents($module_path . '/config/' . $this::FILENAME);
      $yml_data = Yaml::parse($file_contents);

      if (!empty($yml_data[$key])) {
        $value = $yml_data[$key];
      }
    }

    $sync_directory = Settings::get('config_sync_directory');

    if (!empty($sync_directory) && file_exists($sync_directory . '/' . $this::FILENAME)) {
      $file_contents = file_get_contents($sync_directory . '/' . $this::FILENAME);
      $yml_data = Yaml::parse($file_contents);

      if (!empty($yml_data[$key])) {
        $value = $yml_data[$key];
      }
    }

    return $value;
  }
}
