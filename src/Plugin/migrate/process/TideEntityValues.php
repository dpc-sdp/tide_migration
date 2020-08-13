<?php

namespace Drupal\tide_migration\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This plugin returns all field values of an entity as an array.
 *
 * @MigrateProcessPlugin(
 *   id = "tide_entity_values"
 * )
 *
 * Required configuration keys:
 * - source: either the entity ID, or target_id of an array.
 * - type: the entity type.
 *
 * The returned value is an indexed array with entity fields as keys.
 * Example usage:
 * @code
 * process:
 *   uid:
 *     plugin: default_value
 *     default_value: 1
 *   'field_file/target_id':
 *     -
 *       plugin: file_import
 *       source: file_url
 *       destination: constants/file_destination
 *       uid: @uid
 *       skip_on_missing_source: true
 *     -
 *       plugin: entity_values
 *       bundle: file
 *     -
 *       plugin: extract
 *       index:
 *       - fid
 * @endcode
 */
class TideEntityValues extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $pluginDefinition) {
    return new static(
      $configuration,
      $plugin_id,
      $pluginDefinition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_scalar($value) && !empty($value)) {
      $entity_id = $value;
    }
    elseif (!empty($value['target_id'])) {
      $entity_id = $value['target_id'];
    }
    else {
      throw new MigrateException('Cannot retrieve entity ID.');
    }

    if (empty($this->configuration['type'])) {
      throw new MigrateException('Entity type is required.');
    }

    $entity_type = $this->configuration['type'];
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    if ($entity) {
      $entity_values = [
        'id' => $entity->id(),
        'type' => $entity->getEntityTypeId(),
        'bundle' => $entity->bundle(),
        'uuid' => $entity->uuid(),
      ];

      if ($entity instanceof FieldableEntityInterface) {
        $fields = array_keys($entity->getFields());
        foreach ($fields as $field_name) {
          $entity_values[$field_name] = $entity->get($field_name)->value;
        }
      }

      return $entity_values;
    }

    throw new MigrateException('Entity not found.');
  }

}
