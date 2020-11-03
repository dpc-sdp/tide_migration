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
      'nid' => $this->t('Node ID'),
      'vid' => $this->t('Node revision ID'),
      'type' => $this->t('Node bundle'),
      'title' => $this->t('Node title'),
      'uid' => $this->t('Node user id'),
      'created' => $this->t('Node created time - timestamp'),
      'changed' => $this->t('Node changed time - timestamp'),
    ];
    return $fields;
  }


  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'tmns',
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
      'nid',
      'vid',
      'type',
      'title',
      'uid',
      'created',
      'changed',
    ];
    $query = $this->select('node_field_data', 'tmns')
      ->fields('tmns', $fields)
      ->condition('type', $this->configuration['bundle']);
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
    $nid = $row->getSourceProperty('nid');
    $node = Node::load($nid);
    // Loop fields.
    foreach ($this->configuration['field_names'] as $field_name) {
      $value = $node->get($field_name)->getValue();
      $row->setSourceProperty($field_name, $value);
    }
    return parent::prepareRow($row);
  }

}
