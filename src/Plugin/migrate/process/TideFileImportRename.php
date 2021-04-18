<?php

namespace Drupal\tide_migration\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Row;
use Drupal\migrate_file\Plugin\migrate\process\FileImport;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Imports a file from an local or external source and rename the destination.
 *
 * @MigrateProcessPlugin(
 *   id = "tide_file_import_rename"
 * )
 *
 * This plugin extends the file_import plugin with the option to rename the
 * imported file. It accepts all options of file_import with the extra optional
 * config "rename" which has 2 optional keys:
 * - filename
 * - extension
 *
 * If the source file is external, the plugin accepts 2 additional options
 * "retry" and "retry_wait", and will pass them to the download_rename plugin.
 *
 * @code
 * destination:
 *   plugin: entity:node
 * source:
 *   fields:
 *     -
 *       name: product_id
 *       label: 'Product ID'
 *       selector: /product_id
 *     -
 *       name: file_url
 *       label: 'Some file'
 *       selector: /file_url
 *   constants:
 *     file_destination: 'public://path/to/save/'
 *  process:
 *    field_image/target_id:
 *      plugin: file_import_rename
 *      source: file_url
 *      destination: 'constants/file_destination'
 *      id_only: true
 *      file_exists: replace
 *      skip_on_missing_source: true
 *      rename:
 *        filename: product_id
 *      retry: 3
 *      retry_wait: 5
 *    field_image/alt: product_id
 * @endcode
 *
 * @see \Drupal\migrate_file\Plugin\migrate\process\FileImport
 * @see \Drupal\tide_event_atdw\Plugin\migrate\process\DownloadRename
 */
class TideFileImportRename extends FileImport {

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
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, StreamWrapperManagerInterface $stream_wrappers, FileSystemInterface $file_system, MigrateProcessInterface $download_plugin, MigratePluginManager $migrate_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $stream_wrappers, $file_system, $download_plugin);
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
      $container->get('stream_wrapper_manager'),
      $container->get('file_system'),
      $container->get('plugin.manager.migrate.process')->createInstance('tide_download_rename', $configuration),
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->migrateExecutable = $migrate_executable;
    $this->row = $row;
    return parent::transform($value, $migrate_executable, $row, $destination_property);
  }

  /**
   * {@inheritdoc}
   */
  protected function writeFile($source, $destination, $replace = FileSystemInterface::EXISTS_REPLACE) {
    // Rename the destination.
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

      $destination = pathinfo($destination, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR;
      if ($filename) {
        $destination .= $filename;
      }
      if ($extension) {
        $destination .= '.' . $extension;
      }
    }
    return parent::writeFile($source, $destination, $replace);
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

  /**
   * {@inheritdoc}
   */
  protected function getOverwriteMode() {
    if (array_key_exists('file_exists', $this->configuration)) {
      return $this->configuration['file_exists'];
    }
    return parent::getOverwriteMode();
  }

}
