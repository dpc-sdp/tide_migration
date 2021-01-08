<?php

namespace Drupal\tide_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\Exception\BadPluginDefinitionException;
use Drupal\node\Entity\Node;

/**
 * Source plugin for retrieving node values.
 *
 * @MigrateSource(
 *   id = "tide_migration_node_source"
 * )
 *
 * Example usage in migrate config yaml file:
 *
 * @code
 * source:
 *   plugin: tide_migration_node_source
 *   key: default
 *   bundle: tide_landing_page
 *   field_names:
 *     - field_name_1
 *     - field_name_2
 *     - field_name_3
 * @endcode
 *
 */
class TideMigrationNodeSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'entity_id' => $this->t('Node ID'),
    ];
    return $fields;
  }


  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_id' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!isset($this->configuration['bundle'])) {
      throw new BadPluginDefinitionException($this->pluginDefinition['source']['plugin'], 'bundle');
    }
    if (!isset($this->configuration['field_names'])) {
      throw new BadPluginDefinitionException($this->pluginDefinition['source']['plugin'], 'field_names');
    }
    $fields = [
      'entity_id',
    ];
    $types = [
      'card_navigation_auto',
      'card_navigation_featured_auto',
      'card_navigation_featured',
      'card_navigation',
      'card_promotion_auto',
      'card_promotion',
    ];
    $query = $this->select('node__field_landing_page_component', 'n');
    $query->innerJoin('paragraphs_item_field_data', 'p', 'n.field_landing_page_component_target_id=p.id');
    $query->condition('p.parent_field_name', 'field_landing_page_component');
    $or = $query->orConditionGroup();
    foreach ($types as $type) {
      $or->condition('p.type', $type);
    }
    $query->fields('n', $fields)->condition($or);
    $query->distinct();
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!isset($this->configuration['field_names'])) {
      throw new BadPluginDefinitionException($this->pluginDefinition['source']['plugin'], 'field_names');
    }
    // Gets current Node entity.
    $nid = $row->getSourceProperty('entity_id');
    $node = Node::load($nid);
    // Loop fields.
    $row->setSourceProperty('title',$node->title);
    $row->setSourceProperty('nid',$node->id());
    foreach ($this->configuration['field_names'] as $field_name) {
      $value = $node->get($field_name)->getValue();
      $row->setSourceProperty($field_name, $value);
    }
    return parent::prepareRow($row);
  }

}
