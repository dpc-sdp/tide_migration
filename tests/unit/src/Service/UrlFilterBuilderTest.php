<?php

namespace Drupal\Tests\tide_migration\Service;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_plus\DataFetcherPluginInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\tide_migration\Service\UrlFilterBuilder;
use Drupal\Core\Extension\Extension;
use Psr\Http\Message\StreamInterface;

/**
 * @coversDefaultClass \Drupal\tide_migration\Service\UrlFilterBuilder
 * @group tide_migration
 */
class UrlFilterBuilderTest extends UnitTestCase
{

  public function setUp()
  {
    parent::setUp();

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

  public function testBuildInitialUrlFiltersReturnsEmptyArrayIfNoUrlsCanBeGenerated()
  {
    $extension = $this->createMock(Extension::class);
    $extension->method('getPath')
      ->willReturn('.');

    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->method('getModule')
      ->willReturn($extension);

    $dataFetcher = $this->createMock(DataFetcherPluginInterface::class);

    $urlFilterBuilderService = new UrlFilterBuilder($dataFetcher, $moduleHandler);

    $this->assertEquals([], $urlFilterBuilderService->generateUrls([]));
  }

  public function testBuildInitialUrlFiltersReturnUrlsCorrectly()
  {
    $extension = $this->createMock(Extension::class);
    $extension->method('getPath')
      ->willReturn('.');

    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->method('getModule')
      ->willReturn($extension);

    $dataFetcher = $this->createMock(DataFetcherPluginInterface::class);

    $urlFilterBuilderService = new UrlFilterBuilder($dataFetcher, $moduleHandler);

    $configuration = [
      'urls' => [
        [
          'url' => 'https://www.test.com',
          'include' => ['field'],
          'page_filter' => [
            'offset' => 0,
            'limit' => 10,
          ],
          'filters' => [
            'site' => [
              'condition' => [
                'path' => 'field_node_site.tid',
                'operator' => '=',
                'value' => 3
              ]
            ]
          ]
        ]
      ]
    ];

    $this->assertEquals([
      'https://www.test.com?filter%5Bsite%5D%5Bcondition%5D%5Bpath%5D=field_node_site.tid&filter%5Bsite%5D%5Bcondition%5D%5Boperator%5D=%3D&filter%5Bsite%5D%5Bcondition%5D%5Bvalue%5D=3&include=field&page%5Boffset%5D=0&page%5Blimit%5D=10'
    ], $urlFilterBuilderService->buildInitialUrlFilters($configuration));

    $configuration['urls'][0]['filters']['site']['condition']['value'] = '@site';

    $this->assertEquals([
      'https://www.test.com?filter%5Bsite%5D%5Bcondition%5D%5Bpath%5D=field_node_site.tid&filter%5Bsite%5D%5Bcondition%5D%5Boperator%5D=%3D&filter%5Bsite%5D%5Bcondition%5D%5Bvalue%5D=391&include=field&page%5Boffset%5D=0&page%5Blimit%5D=10'
    ], $urlFilterBuilderService->buildInitialUrlFilters($configuration));
  }

  public function offsetUrlContentProvider() {
    return [
      [
        [
          'links' => [
            'last' => [
              'href' => 'https://www.test.com?page[offset]=10&page[limit]=10'
            ]
          ]
        ],
        [
          'https://www.test.com?page%5Boffset%5D=0&page%5Blimit%5D=10',
          'https://www.test.com?page%5Boffset%5D=10&page%5Blimit%5D=10'
        ]
      ],
      [
        [],
        [
          'https://www.test.com?filter%5Bsite%5D%5Bcondition%5D%5Bpath%5D=field_node_site.tid&filter%5Bsite%5D%5Bcondition%5D%5Boperator%5D=%3D&filter%5Bsite%5D%5Bcondition%5D%5Bvalue%5D=3&include=field&page%5Boffset%5D=0&page%5Blimit%5D=10'
        ]
      ],
      [
        [
          'links' => [
            'last' => [
              'href' => 'https://www.test.com'
            ]
          ]
        ],
        [
          'https://www.test.com?filter%5Bsite%5D%5Bcondition%5D%5Bpath%5D=field_node_site.tid&filter%5Bsite%5D%5Bcondition%5D%5Boperator%5D=%3D&filter%5Bsite%5D%5Bcondition%5D%5Bvalue%5D=3&include=field&page%5Boffset%5D=0&page%5Blimit%5D=10',
        ]
      ],
      [
        [
          'links' => [
            'last' => [
              'href' => 'https://www.test.com?test=test'
            ]
          ]
        ],
        [
          'https://www.test.com?filter%5Bsite%5D%5Bcondition%5D%5Bpath%5D=field_node_site.tid&filter%5Bsite%5D%5Bcondition%5D%5Boperator%5D=%3D&filter%5Bsite%5D%5Bcondition%5D%5Bvalue%5D=3&include=field&page%5Boffset%5D=0&page%5Blimit%5D=10',
        ]
      ],
    ];
  }

  /**
   * @dataProvider offsetUrlContentProvider
   */
  public function testGenerateOffsetUrlReturnUrlsCorrectly($contents, $expected)
  {
    $extension = $this->createMock(Extension::class);
    $extension->method('getPath')
      ->willReturn('.');

    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->method('getModule')
      ->willReturn($extension);

    $stream = $this->createMock(StreamInterface::class);
    $stream->method('getContents')
      ->willReturn(json_encode($contents));

    $dataFetcher = $this->createMock(DataFetcherPluginInterface::class);
    $dataFetcher->method('getResponseContent')
      ->willReturn($stream);

    $urlFilterBuilderService = new UrlFilterBuilder($dataFetcher, $moduleHandler);

    $configuration = [
      'urls' => [
        [
          'url' => 'https://www.test.com',
          'include' => ['field'],
          'page_filter' => [
            'offset' => 0,
            'limit' => 10,
          ],
          'filters' => [
            'site' => [
              'condition' => [
                'path' => 'field_node_site.tid',
                'operator' => '=',
                'value' => 3
              ]
            ]
          ]
        ]
      ]
    ];

    $this->assertEquals($expected, $urlFilterBuilderService->generateUrls($configuration));
  }

}
