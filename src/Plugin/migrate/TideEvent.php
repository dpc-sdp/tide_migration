<?php

namespace Drupal\tide_migration\Plugin\migrate;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\RequirementsInterface;

/**
 * Plugin class for Drupal 8 event migrations.
 */
class TideEvent extends Migration {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    // Check whether the current migration source and destination plugin
    // requirements are met or not.
    if ($this->getSourcePlugin() instanceof RequirementsInterface) {
      $this->getSourcePlugin()->checkRequirements();
    }
    if ($this->getDestinationPlugin() instanceof RequirementsInterface) {
      $this->getDestinationPlugin()->checkRequirements();
    }

    if (empty($this->requirements)) {
      // There are no requirements to check.
      return;
    }
    /** @var \Drupal\migrate\Plugin\MigrationInterface[] $required_migrations */
    $required_migrations = $this->getMigrationPluginManager()->createInstances($this->requirements);

    $missing_migrations = array_diff($this->requirements, array_keys($required_migrations));
    // Check if the dependencies are in good shape.
    foreach ($required_migrations as $migration_id => $required_migration) {
      if ($required_migration->getIdMap()->processedCount() == 0) {
        $missing_migrations[] = $migration_id;
      }
    }
    if ($missing_migrations) {
      throw new RequirementsException('Missing migrations ' . implode(', ', $missing_migrations) . '.', ['requirements' => $missing_migrations]);
    }
  }

}
