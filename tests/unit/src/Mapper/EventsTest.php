<?php

namespace Drupal\Tests\tide_migration\Mapper;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\tide_migration\Mapper\Events;

/**
 * @coversDefaultClass \Drupal\tide_migration\Mapper\Events
 * @group tide_migration
 */
class EventsTest extends UnitTestCase
{

  public function setUp()
  {
    parent::setUp();

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

  public function testMappingOfEventsReturnNullOnEmptyArray()
  {
    $eventsMapper = new Events();
    $this->assertEquals(NULL, $eventsMapper->convert([]));
  }

  public function testMappingOfEventsReturnEventsArray()
  {
    $json_data = file_get_contents(__DIR__ . '/../json/correct_event_json_data');
    $json_contents = json_decode($json_data, TRUE);

    $eventsMapper = new Events();
    $events = $eventsMapper->convert($json_contents);

    $this->assertArrayHasKey('field_event_category', $events);
    $this->assertArrayHasKey('field_event_requirements', $events);
    $this->assertArrayHasKey('field_event_details', $events);
    $this->assertArrayHasKey('field_topic', $events);
    $this->assertArrayHasKey('field_tags', $events);
    $this->assertArrayHasKey('field_audience', $events);
    $this->assertArrayHasKey('field_media_image', $events);
    $this->assertArrayHasKey('field_featured_image', $events);
    $this->assertArrayHasKey('events', $events);

    $this->assertCount(2, $events['field_event_category']);
    $this->assertCount(2, $events['field_event_requirements']);
    $this->assertCount(2, $events['field_event_details']);
    $this->assertCount(1, $events['field_topic']);
    $this->assertCount(10, $events['field_tags']);
    $this->assertCount(2, $events['field_audience']);
    $this->assertCount(2, $events['field_media_image']);
    $this->assertCount(2, $events['field_featured_image']);
    $this->assertCount(2, $events['events']);

    $this->assertCount(1, $events['events'][0]['field_event_category']);
    $this->assertCount(1, $events['events'][0]['field_event_details']);
    $this->assertCount(1, $events['events'][0]['field_event_details'][0]['field_event_requirements']);
    $this->assertCount(1, $events['events'][0]['field_topic']);
    $this->assertCount(3, $events['events'][0]['field_tags']);
    $this->assertCount(2, $events['events'][0]['field_audience']);
  }
}
