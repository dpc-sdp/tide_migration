<?php

namespace Drupal\tide_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;


/**
 * Perform custom value transformations.
 *
 * @MigrateProcessPlugin(
 *   id = "tide_migration_lookup_taxonomy"
 * )
 *
 * To do custom value transformations use the following:
 *
 * @code
 * field_text:
 *   plugin: tide_migration_lookup_taxonomy
 *   migration: migration_id
 *   source: taxonomy array: ['taxonomy' => ['parent' => ['vid' => 'vocabulary name']], 'term' => ['drupal_internal__tid' => 123, 'name' => 'abc']]
 * @endcode
 *
 */


/**
 * This plugin looks up taxonomy in migration first and if not found, it will look into existing terms already loaded on the website.
 *
 * @MigrateProcessPlugin(
 *   id = "tide_migration_lookup_taxonomy"
 * )
 *
 * Required configuration keys:
 * - migration: Which migration should be used to lookup taxonomy.
 * - source: Source needs to be an array of values that will be used to lookup taxonomy.
 *
 * The returned value is an taxonomy id.
 * Example usage:
 * @code
 * field_text:
 *   plugin: tide_migration_lookup_taxonomy
 *   migration: migration_id
 *   source: taxonomy array: ['taxonomy' => ['parent' => ['vid' => 'vocabulary name']], 'term' => ['drupal_internal__tid' => 123, 'name' => 'abc']]
 * @endcode
 */
class TideMigrationLookupTaxonomy extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $migration = $this->configuration['migration'];
    $lookupTid =  $this->lookupTaxonomyInMigration($migration, $value);

    if (empty($lookupTid)) {
      $lookupTid = $this->lookupTaxonomyInExistingTerms($value);
    }


    return $lookupTid;
  }

  /**
   * @param $value
   * @return int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function lookupTaxonomyInExistingTerms($value) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $taxonomy_term_service */
    $taxonomy_term_service = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $tree = $taxonomy_term_service->loadTree(strtolower($value['parent']));
    $term = $value['name'];

    foreach ($tree as $item) {
      if (strtolower($item->name) === strtolower($term)) {
        return $item->tid;
      }
    }

    return 0;
  }

  /**
   * @param $migration
   * @param $value
   * @return int
   */
  private function lookupTaxonomyInMigration($migration, $value) {
    $database = \Drupal::database();
    $query = $database->select('migrate_map_' . $migration, 'migration');
    $query->condition('migration.sourceid1', $value['drupal_internal__tid'], '=');
    $query->fields('migration', ['sourceid1', 'destid1']);

    $response = $query->execute()->fetch();

    if (!empty($response) && !empty($response->destid1)) {
      return $response->destid1;
    }

    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }
}
