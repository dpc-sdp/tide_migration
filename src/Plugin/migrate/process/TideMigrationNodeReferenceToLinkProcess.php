<?php

namespace Drupal\tide_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;

/**
 * This plugin currently node reference to link.
 *
 * @MigrateProcessPlugin(
 *   id = "tide_migration_node_reference_to_link_process",
 * )
 *
 * @code
 * source:
 *   plugin: tide_migration_node_reference_to_link_process
 *   source: value
 * @endcode
 *
 */
class TideMigrationNodeReferenceToLinkProcess extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $id = $row->getSourceProperty('id');
    $parent_id = $row->getSourceProperty('parent_id');
    if (!$value) {
      throw new MigrateSkipProcessException('paragraph id=' . $id . ', node id=' . $parent_id, TRUE);
    }
    if (!isset($value['target_id'])) {
      throw new MigrateSkipProcessException('paragraph id =' . $id . ', node id=' . $parent_id, TRUE);
    }
    if (isset($value['target_id'])) {
      if (Node::load($value['target_id'])) {
        return 'entity:node/' . $value['target_id'];
      }
      else {
        return 'internal:/node/' . $value['target_id'];
      }
    }
    throw new MigrateSkipProcessException('paragraph id =' . $id . ', node id=' . $parent_id, TRUE);
  }
}
