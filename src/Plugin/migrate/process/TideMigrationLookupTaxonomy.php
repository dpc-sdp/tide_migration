<?php

namespace Drupal\tide_migration\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\tide_migration\Service\TaxonomyLookup;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class TideMigrationLookupTaxonomy extends ProcessPluginBase implements ContainerFactoryPluginInterface
{

  /**
   * The currently running migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * @var TaxonomyLookup
   */
  protected $taxonomy_lookup;

  /**
   * TideGenerateEventDetailsParagraph constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param TaxonomyLookup $taxonomy_lookup
   * @param MigrationInterface|null $migration
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TaxonomyLookup $taxonomy_lookup,
    MigrationInterface $migration
  )
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!$migration instanceof MigrationInterface) {
      throw new \InvalidArgumentException("The fifth argument to " . __METHOD__ . " must be an instance of MigrationInterface.");
    }

    $this->migration = $migration;
    $this->taxonomy_lookup = $taxonomy_lookup;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      new TaxonomyLookup(),
      $migration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property)
  {
    $migration = $this->configuration['migration'];
    $lookupTid = $this->taxonomy_lookup->lookupTaxonomyInMigration($migration, $value['drupal_internal__tid']);

    if (empty($lookupTid)) {
      $lookupTid = $this->taxonomy_lookup->lookupTaxonomyInExistingTerms($value['name'], $value['parent']);
    }

    return $lookupTid;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple()
  {
    return TRUE;
  }
}
