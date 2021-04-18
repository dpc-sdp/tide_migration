<?php

namespace Drupal\tide_migration\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Download;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Row;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Downloads a file from a HTTP(S) remote location with the option to rename.
 *
 * @MigrateProcessPlugin(
 *   id = "tide_download_rename"
 * )
 *
 * This plugin accepts all options of the download plugin with the extra
 * optional configs:
 * - rename: an array with 2 optional keys: filename and extension
 * - retry: number of attempts to download the file.
 * - retry_wait: number of seconds to wait between the failed attempts.
 *
 * @code
 * source:
 *   constants:
 *     ext_jpeg: 'jpg'
 * process:
 *   plugin: download
 *   source:
 *     - source_url
 *     - destination_uri
 *   file_exists: replace
 *   rename:
 *     extension: 'constants/ext_jpeg'
 * @endcode
 */
class TideDownloadRename extends Download {

  /**
   * The MigratePluginManager instance.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManagerInterface
   */
  protected $migratePluginManager;

  /**
   * The current migration.
   *
   * @var \Drupal\migrate\MigrateExecutableInterface
   */
  protected $migrateExecutable;

  /**
   * The row from the source to process.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FileSystemInterface $file_system, Client $http_client, MigratePluginManager $migrate_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $file_system, $http_client);
    $this->migratePluginManager = $migrate_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
      $container->get('http_client'),
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($row->isStub()) {
      return NULL;
    }

    $this->migrateExecutable = $migrate_executable;
    $this->row = $row;
    $retry = $this->configuration['retry'] ?? 0;
    $wait = $this->configuration['retry_wait'] ?? 5;

    list($source, $destination) = $value;
    // Attempt to download with retry.
    do {
      $retry--;
      try {
        $destination = parent::transform($value, $migrate_executable, $row, $destination_property);
        // Success, stop the retry.
        $retry = 0;
      }
      catch (MigrateException $exception) {
        if ($retry > 0) {
          $migrate_executable->saveMessage($exception->getMessage() . '. Retry in ' . $wait . ' seconds.');
          sleep($wait);
        }
        else {
          throw $exception;
        }
      }
    } while ($retry > 0);

    // Rename the downloaded file.
    if (!empty($this->configuration['file_rename'])) {
      // Change file name.
      $filename = pathinfo($destination, PATHINFO_FILENAME);
      if (!empty($this->configuration['file_rename']['filename'])) {
        $new_filename = $this->getTransformedValue($this->configuration['file_rename']['filename']);

        if ($new_filename) {
          $filename = $new_filename;
        }
      }

      // Change file extension.
      $extension = pathinfo($destination, PATHINFO_EXTENSION);
      if (!empty($this->configuration['file_rename']['extension'])) {
        $new_extension = $this->getTransformedValue($this->configuration['file_rename']['extension']);
        if ($new_extension) {
          $extension = $new_extension;
        }
      }

      $renamed_destination = pathinfo($destination, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR;
      if ($filename) {
        $renamed_destination .= $filename;
      }
      if ($extension) {
        $renamed_destination .= '.' . $extension;
      }

      // Move downloaded file to the desired destination.
      $final_destination = $this->fileSystem->move($destination, $renamed_destination, $this->configuration['file_exists']);
      if ($final_destination) {
        return $final_destination;
      }

      throw new MigrateException("File $source could not be downloaded to $renamed_destination");
    }

    return $destination;
  }

  /**
   * Transform a destination value.
   *
   * @param mixed $value
   *   The value to transform.
   *
   * @return mixed
   *   The transformed value.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getTransformedValue($value) {
    if (is_array($value)) {
      $getProcessPlugin = $this->migratePluginManager->createInstance($value['plugin'], $value);
    }
    else {
      $getProcessPlugin = $this->migratePluginManager->createInstance('get', ['source' => $value]);
    }
    return $getProcessPlugin->transform(NULL, $this->migrateExecutable, $this->row, $value);
  }

}
