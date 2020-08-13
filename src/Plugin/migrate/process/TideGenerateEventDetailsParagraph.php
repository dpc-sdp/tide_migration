<?php

namespace Drupal\tide_migration\Plugin\migrate\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This plugin generates a event details paragraph based on the array passed.
 *
 * @MigrateProcessPlugin(
 *   id = "tide_generate_event_details_paragraph"
 * )
 *
 * Required configuration keys:
 * - source: Source needs to be an array of values that will be used to generate paragraph.
 *
 * The returned value is an array with target id and target revision id.
 * Example usage:
 * @code
 * field_text:
 *   plugin: tide_generate_event_details_paragraph
 *   source: array
 *    -  date_range
 *    -  price_from
 *    -  price_to
 *    -  link
 *    -  location
 *    -  show_time
 *    -  event_requirements
 * @endcode
 */
class TideGenerateEventDetailsParagraph extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The currently running migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paragraph_storage;

  /**
   * TideGenerateEventDetailsParagraph constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param EntityStorageInterface $paragraph
   * @param MigrationInterface|null $migration
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityStorageInterface $paragraph,
    MigrationInterface $migration = NULL
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!$migration instanceof MigrationInterface) {
      throw new \InvalidArgumentException("The fifth argument to " . __METHOD__ . " must be an instance of MigrationInterface.");
    }

    $this->migration = $migration;
    $this->paragraph_storage = $paragraph;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('paragraph'),
      $migration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      $date_range = $value[1];
      $price_from = $value[2];
      $price_to = $value[3];
      $link = $value[4];
      $location = $value[5];
      $show_time = $value[6];
      $event_requirements = $value[7];

      return $this->generateParagraphEventDetails(
        $date_range,
        $price_from,
        $price_to,
        $link,
        $location,
        $show_time,
        $event_requirements
      );
    }

    return FALSE;
  }

  /**
   * @param array|null $date_range
   * @param string|null $price_from
   * @param string|null $price_to
   * @param array|null $link
   * @param array|null $location
   * @param bool $show_time
   * @param array|null $event_requirements
   * @return int[]
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function generateParagraphEventDetails(
    ?array $date_range = NULL,
    ?string $price_from = NULL,
    ?string $price_to = NULL,
    ?array $link = [],
    ?array $location = [],
    bool $show_time = FALSE,
    ?array $event_requirements = []
  ) {

    $paragraph_entity['type'] =  'event_details';
    $paragraph_entity['field_show_time'] = [
      'value' => $show_time
    ];

    if (!empty($date_range)) {
      $paragraph_entity['field_paragraph_date_range'] = [
        'value'  =>  '2021-03-15T05:11:00',//$date_range['value'],
        'end_value' => '2021-09-15T05:11:00',//$date_range['end_value'],
      ];
    }

    if (!empty($link)) {
      $paragraph_entity['field_paragraph_link'] = [
        'uri'  =>  $link['uri'],
        'title' => $link['title'],
        'options' => $link['options'],
      ];
    }

    if (!empty($location)) {
      $paragraph_entity['field_paragraph_location'] = [
        'langcode'  =>  $location['langcode'],
        'country_code'  =>  $location['country_code'],
        'administrative_area'  =>  $location['administrative_area'],
        'locality'  =>  $location['locality'],
        'dependent_locality'  =>  $location['dependent_locality'],
        'postal_code'  =>  $location['postal_code'],
        'sorting_code'  =>  $location['sorting_code'],
        'address_line1'  =>  $location['address_line1'],
        'address_line2'  =>  $location['address_line2'],
        'given_name'  =>  $location['given_name'],
        'additional_name'  =>  $location['additional_name'],
        'family_name'  =>  $location['family_name'],
      ];
    }

    if (!empty($price_from)) {
      $paragraph_entity['field_paragraph_event_price_from'] = [
        'from_value'  =>  $price_from,
      ];
    }

    if (!empty($price_to)) {
      $paragraph_entity['field_paragraph_event_price_to'] = [
        'from_value'  =>  $price_to,
      ];
    }

    $paragraph = $this->paragraph_storage->create($paragraph_entity);

    $paragraph->save();

    return [(int) $paragraph->id(), (int) $paragraph->getRevisionId()];
  }
}
