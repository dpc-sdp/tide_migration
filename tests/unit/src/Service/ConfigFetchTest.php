<?php

namespace Drupal\Tests\tide_migration\Service;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\tide_migration\Service\ConfigFetch;
use Drupal\Core\Extension\Extension;

/**
 * @coversDefaultClass \Drupal\tide_migration\Service\ConfigFetch
 * @group tide_migration
 */
class ConfigFetchTest extends UnitTestCase
{

  public function setUp()
  {
    parent::setUp();

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

  public function testConfigFetchReturnsCorrectValue()
  {
    $extension = $this->createMock(Extension::class);
    $extension->method('getPath')
      ->willReturn('.');

    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->method('getModule')
      ->willReturn($extension);

    $eventsMapper = new ConfigFetch($moduleHandler);

    $this->assertEquals(391, $eventsMapper->fetchValue('site'));
  }

}
