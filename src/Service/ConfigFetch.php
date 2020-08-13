<?php
namespace Drupal\tide_migration\Service;

use Drupal\Core\Site\Settings;
use Symfony\Component\Yaml\Yaml;

class ConfigFetch {

  const FILENAME = 'tide_migrations.settings.yml';

  public function fetchValue($key) {
    $value = NULL;
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('tide_migration')->getPath();

    if (file_exists($module_path . '/config/' . $this::FILENAME)) {
      $file_contents = file_get_contents($module_path . '/config/' . $this::FILENAME);
      $yml_data = Yaml::parse($file_contents);

      if (!empty($yml_data[$key])) {
        $value = $yml_data[$key];
      }
    }

    $sync_directory = Settings::get('config_sync_directory');

    if (file_exists($sync_directory . '/' . $this::FILENAME)) {
      $file_contents = file_get_contents($sync_directory . '/' . $this::FILENAME);
      $yml_data = Yaml::parse($file_contents);

      if (!empty($yml_data[$key])) {
        $value = $yml_data[$key];
      }
    }

    return $value;
  }
}
