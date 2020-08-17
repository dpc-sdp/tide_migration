<?php

namespace Drupal\tide_migration\Service;

class TaxonomyLookup {

  /**
   * @param $value
   * @return int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function lookupTaxonomyInExistingTerms($term, $parent) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $taxonomy_term_service */
    $taxonomy_term_service = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $tree = $taxonomy_term_service->loadTree(strtolower($parent));

    foreach ($tree as $item) {
      if (strtolower($item->name) === strtolower($term)) {
        return $item->tid;
      }
    }

    return 0;
  }

  /**
   * @param string $migration
   * @param int $value
   * @return int
   */
  public function lookupTaxonomyInMigration($migration, $tid) {
    $database = \Drupal::database();
    $query = $database->select('migrate_map_' . $migration, 'migration');
    $query->condition('migration.sourceid1', $tid, '=');
    $query->fields('migration', ['sourceid1', 'destid1']);

    $response = $query->execute()->fetch();

    if (!empty($response) && !empty($response->destid1)) {
      return $response->destid1;
    }

    return 0;
  }
}
