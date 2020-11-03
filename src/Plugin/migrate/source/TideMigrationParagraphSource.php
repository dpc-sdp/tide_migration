<?php

namespace Drupal\tide_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\Exception\BadPluginDefinitionException;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Source plugin for retrieving paragraph values.
 *
 * @MigrateSource(
 *   id = "tide_migration_paragraph_source"
 * )
 *
 * Example usage in migrate config yaml file:
 *
 * @code
 * source:
 *   plugin: tide_migration_paragraph_source
 *   key: default
 *   bundle: paragraph_a
 *   field_names:
 *     - field_name_1
 *     - field_name_2
 *     - field_name_3
 * @endcode
 *
 */
class TideMigrationParagraphSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Paragraph ID'),
      'revision_id' => $this->t('Paragraph revision ID'),
      'type' => $this->t('Paragraph bundle'),
      'parent_id' => $this->t('Node title'),
      'parent_type' => $this->t('Parent entity type'),
      'parent_field_name' => $this->t('Parent entity field name'),
      'created' => $this->t('Paragraph created time - timestamp'),
    ];
    return $fields;
  }


  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'tmps',
      ],
      'parent_id' => [
        'type' => 'integer',
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
      'id',
      'revision_id',
      'type',
      'parent_id',
      'parent_type' ,
      'parent_field_name',
      'created',
    ];
    $query = $this->select('paragraphs_item_field_data', 'tmps')
      ->fields('tmps', $fields)
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
    // Gets current Paragraph entity.
    $paragraph_id = $row->getSourceProperty('id');
    $paragraph = Paragraph::load($paragraph_id);
    // Loop fields.
    foreach ($this->configuration['field_names'] as $field_name) {
      $value = $paragraph->get($field_name)->getValue();
      $row->setSourceProperty($field_name, $value);
    }
    return parent::prepareRow($row);
  }

}
