<?php

namespace Drupal\tide_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * This plugin currently can only be used in nodes migration for returning
 * paragraph values.
 *
 * @MigrateProcessPlugin(
 *   id = "tide_migration_paragraph_process",
 *   handle_multiples = TRUE
 * )
 *
 * @code
 * source:
 *   plugin: tide_migration_paragraph_process
 *   migrate_ids:
 *     - migrate_paragraph_example
 *     - migrate_paragraph_example_b
 *   target_field: field_components
 *   overwrite: false
 * @endcode
 *
 */
class TideMigrationParagraphProcess extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['migrate_ids'])) {
      throw new MigrateException('migrate_ids is empty');
    }
    if (empty($this->configuration['target_field'])) {
      throw new MigrateException('target_field is empty');
    }
    $paragraphs = [];
    foreach ($this->configuration['migrate_ids'] as $migrate_id) {
      $results = \Drupal::database()
        ->select('migrate_map_' . $migrate_id, $migrate_id)
        ->fields($migrate_id, ['destid1', 'destid2'])
        ->condition($migrate_id . '.sourceid2', $row->getSourceProperty('nid'), '=')
        ->execute()
        ->fetchAll();
      if (!empty($results)) {
        foreach ($results as $result) {
          $paragraphs[] = [
            'target_id' => $result->destid1,
            'target_revision_id' => $result->destid2,
          ];
        }
      }
    }
    if ($this->configuration['overwrite'] == TRUE){
      return $paragraphs;
    }
    return array_merge($paragraphs, $row->getSourceProperty($this->configuration['target_field']));
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
