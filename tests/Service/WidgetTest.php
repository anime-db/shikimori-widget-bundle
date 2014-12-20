<?php
/**
 * AnimeDb package
*
* @package   AnimeDb
* @author    Peter Gribanov <info@peter-gribanov.ru>
* @copyright Copyright (c) 2011, Peter Gribanov
* @license   http://opensource.org/licenses/GPL-3.0 GPL v3
*/

namespace AnimeDb\Bundle\ShikimoriWidgetBundle\Tests\Service;

use AnimeDb\Bundle\ShikimoriWidgetBundle\Service\Widget;

/**
 * Test widget
 *
 * @package AnimeDb\Bundle\ShikimoriWidgetBundle\Tests\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class WidgetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Widget
     *
     * @var \AnimeDb\Bundle\ShikimoriWidgetBundle\Service\Widget
     */
    protected $widget;

    /**
     * Browser
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $browser;

    /**
     * Repository
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * Locale
     *
     * @var string
     */
    protected $locale = 'en';

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->getWidget($this->locale);
    }

    /**
     * Get widget
     *
     * @param string $locale
     *
     * @return \AnimeDb\Bundle\ShikimoriWidgetBundle\Service\Widget
     */
    protected function getWidget($locale)
    {
        $this->browser = $this->getMockBuilder('\AnimeDb\Bundle\ShikimoriBrowserBundle\Service\Browser')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repository = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository)
            ->with('AnimeDbCatalogBundle:Source');

        $this->widget = new Widget($this->browser, $doctrine, $locale);
        $this->widget->setFiller();

        return $this->widget;
    }

    /**
     * Get sources
     *
     * @return array
     */
    public function getSources()
    {
        return [
            [
                0,
                []
            ],
            [
                0,
                [
                    'http://google.com/',
                    'http://example.com/'
                ]
            ],
            [
                1,
                [
                    'http://example.com/animes/1-foo',
                    'http://example.com/animes/2-bar'
                ]
            ],
            [
                123,
                [
                    'http://example.com/animes/123-foo',
                    'http://example.com/animes/4-bar'
                ]
            ]
        ];
    }

    /**
     * Test get item id
     *
     * @dataProvider getSources
     *
     * @param integer $expected
     * @param array $sources
     */
    public function testGetItemId($expected, array $sources)
    {
        // build sources list mock objects
        foreach ($sources as $key => $source) {
            $sources[$key] = $this->getMock('\AnimeDb\Bundle\CatalogBundle\Entity\Source');
            $sources[$key]
                ->expects($this->any())
                ->method('getUrl')
                ->willReturn($source);
        }
        $this->browser
            ->expects($this->any())
            ->method('getHost')
            ->willReturn('http://example.com');
        $item = $this->getMock('\AnimeDb\Bundle\CatalogBundle\Entity\Item');
        $item
            ->expects($this->once())
            ->method('getSources')
            ->willReturn($sources);
        // test
        $this->assertEquals($expected, $this->widget->getItemId($item));
    }

    /**
     * Get hash
     *
     * @return array
     */
    public function getHash()
    {
        return [
            [
                md5(''),
                []
            ],
            [
                md5(':1'),
                [['id' => 1]]
            ],
            [
                md5(':3:1:2'),
                [['id' => 3], ['id' => 1], ['id' => 2]]
            ]
        ];
    }

    /**
     * Test hash
     *
     * @dataProvider getHash
     *
     * @param string $expected
     * @param array $list
     */
    public function testHash($expected, array $list)
    {
        $this->assertEquals($expected, $this->widget->hash($list));
    }

    /**
     * Get items
     *
     * @return array
     */
    public function getItems()
    {
        $host = 'http://example.com';
        return [
            [
                'ru',
                'foo',
                [
                    $host.'/bar'
                ],
                [
                    'name' => 'foo',
                    'russian' => '',
                    'url' => '/bar',
                    'image' => [
                        'original' => 'baz'
                    ],
                ]
            ],
            [
                'ru',
                'bar',
                [
                    $host.'/bar'
                ],
                [
                    'name' => 'foo',
                    'russian' => 'bar',
                    'url' => '/bar',
                    'image' => [
                        'original' => 'baz'
                    ],
                    'world_art_id' => '',
                    'myanimelist_id' => '',
                    'ani_db_id' => ''
                ]
            ],
            [
                'ja',
                'foo',
                [
                    $host.'/bar',
                    str_replace('#ID#', 123, Widget::WORLD_ART_URL)
                ],
                [
                    'name' => 'foo',
                    'japanese' => [],
                    'url' => '/bar',
                    'image' => [
                        'original' => 'baz'
                    ],
                    'world_art_id' => 123,
                    'myanimelist_id' => '',
                    'ani_db_id' => ''
                ]
            ],
            [
                'ja',
                'bar',
                [
                    $host.'/bar',
                    str_replace('#ID#', 123, Widget::MY_ANIME_LIST_URL)
                ],
                [
                    'name' => 'foo',
                    'japanese' => [
                        'bar'
                    ],
                    'url' => '/bar',
                    'image' => [
                        'original' => 'baz'
                    ],
                    'world_art_id' => '',
                    'myanimelist_id' => 123,
                    'ani_db_id' => ''
                ]
            ],
            [
                'en',
                'foo',
                [
                    $host.'/bar',
                    str_replace('#ID#', 123, Widget::ANI_DB_URL)
                ],
                [
                    'name' => 'foo',
                    'english' => [],
                    'url' => '/bar',
                    'image' => [
                        'original' => 'baz'
                    ],
                    'world_art_id' => '',
                    'myanimelist_id' => '',
                    'ani_db_id' => 123
                ]
            ],
            [
                'en',
                'foo',
                [
                    $host.'/bar',
                    str_replace('#ID#', 1, Widget::WORLD_ART_URL),
                    str_replace('#ID#', 2, Widget::MY_ANIME_LIST_URL),
                    str_replace('#ID#', 3, Widget::ANI_DB_URL)
                ],
                [
                    'name' => 'foo',
                    'english' => [],
                    'url' => '/bar',
                    'image' => [
                        'original' => 'baz'
                    ],
                    'world_art_id' => 1,
                    'myanimelist_id' => 2,
                    'ani_db_id' => 3
                ]
            ],
            [
                'en',
                'bar',
                [
                    $host.'/bar'
                ],
                [
                    'name' => 'foo',
                    'english' => [
                        'bar'
                    ],
                    'url' => '/bar',
                    'image' => [
                        'original' => 'baz'
                    ]
                ],
                null,
                $host.'/link/to/fill/item'
            ],
            [
                'fr',
                'foo',
                [
                    $host.'/bar'
                ],
                [
                    'name' => 'foo',
                    'url' => '/bar',
                    'image' => [
                        'original' => 'baz'
                    ]
                ],
                $this->getMock('\AnimeDb\Bundle\CatalogBundle\Entity\Item')
            ]
        ];
    }

    /**
     * Test get widget item
     *
     * @dataProvider getItems
     *
     * @param string $locale
     * @param string $name
     * @param array $sources
     * @param array $info
     * @param \PHPUnit_Framework_MockObject_MockObject|null $item
     * @param string $fill_link
     */
    public function testGetWidgetItem(
        $locale,
        $name,
        array $sources,
        array $info,
        \PHPUnit_Framework_MockObject_MockObject $item = null,
        $fill_link = ''
    ) {
        $host = 'http://example.com';
        $this->getWidget($locale);
        $this->browser
            ->expects($this->atLeastOnce())
            ->method('getHost')
            ->willReturn($host);
        $source = null;
        if ($item) {
            $source = $this->getMock('\AnimeDb\Bundle\CatalogBundle\Entity\Source');
            $source
                ->expects($this->once())
                ->method('getItem')
                ->willReturn($item);
        }
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($source)
            ->with(['url' => $sources]);
        // add filler
        if ($fill_link) {
            $filler = $this->getMockBuilder('\AnimeDb\Bundle\ShikimoriFillerBundle\Service\Filler')
                ->disableOriginalConstructor()
                ->getMock();
            $filler
                ->expects($this->once())
                ->method('getLinkForFill')
                ->willReturn($fill_link)
                ->with($sources[0]);
            $this->widget->setFiller($filler);
        }

        // test
        $entity = $this->widget->getWidgetItem($info);

        $this->assertEquals($name, $entity->getName());
        $this->assertEquals($host.$info['url'], $entity->getLink());
        $this->assertEquals($host.$info['image']['original'], $entity->getCover());
        $this->assertEquals($item, $entity->getItem());
        if (!$item && $fill_link) {
            $this->assertEquals($fill_link, $entity->getLinkForFill());
        } else {
            $this->assertEmpty($entity->getLinkForFill());
        }
    }

    /**
     * Get names
     *
     * @return array
     */
    public function getNames()
    {
        return [
            [
                'foo',
                'ru',
                [
                    'name' => 'foo',
                    'russian' => '',
                ]
            ],
            [
                'bar',
                'ru',
                [
                    'name' => 'foo',
                    'russian' => 'bar',
                ]
            ],
            [
                'foo',
                'ja',
                [
                    'name' => 'foo',
                    'japanese' => [],
                ]
            ],
            [
                'bar',
                'ja',
                [
                    'name' => 'foo',
                    'japanese' => ['bar'],
                ]
            ],
            [
                'foo',
                'en',
                [
                    'name' => 'foo',
                    'english' => [],
                ]
            ],
            [
                'bar',
                'en',
                [
                    'name' => 'foo',
                    'english' => ['bar'],
                ]
            ]
        ];
    }

    /**
     * Test get item name
     *
     * @dataProvider getNames
     *
     * @param string $expected
     * @param string $locale
     * @param array $item
     */
    public function testGetItemName($expected, $locale, array $item)
    {
        $this->assertEquals($expected, $this->getWidget($locale)->getItemName($item));
    }

    public function getItemSources()
    {
        return [
            [
                [
                    'http://example.com/foo'
                ],
                [
                    'url' => '/foo'
                ]
            ],
            [
                [
                    'http://example.com/foo'
                ],
                [
                    'url' => '/foo',
                    'world_art_id' => '',
                    'myanimelist_id' => '',
                    'ani_db_id' => ''
                ]
            ],
            [
                [
                    'http://example.com/foo',
                    str_replace('#ID#', 1, Widget::WORLD_ART_URL),
                    str_replace('#ID#', 2, Widget::MY_ANIME_LIST_URL),
                    str_replace('#ID#', 3, Widget::ANI_DB_URL)
                ],
                [
                    'url' => '/foo',
                    'world_art_id' => 1,
                    'myanimelist_id' => 2,
                    'ani_db_id' => 3
                ]
            ],
            [
                [
                    'http://example.com/foo',
                    str_replace('#ID#', 1, Widget::WORLD_ART_URL)
                ],
                [
                    'url' => '/foo',
                    'world_art_id' => 1,
                    'myanimelist_id' => '',
                    'ani_db_id' => ''
                ]
            ],
            [
                [
                    'http://example.com/foo',
                    str_replace('#ID#', 1, Widget::MY_ANIME_LIST_URL)
                ],
                [
                    'url' => '/foo',
                    'world_art_id' => '',
                    'myanimelist_id' => 1,
                    'ani_db_id' => ''
                ]
            ],
            [
                [
                    'http://example.com/foo',
                    str_replace('#ID#', 1, Widget::ANI_DB_URL)
                ],
                [
                    'url' => '/foo',
                    'world_art_id' => '',
                    'myanimelist_id' => '',
                    'ani_db_id' => 1
                ]
            ]
        ];
    }

    /**
     * Test get catalog item fail
     *
     * @dataProvider getItemSources
     *
     * @param array $sources
     * @param array $item
     */
    public function testGetCatalogItemFail(array $sources, array $item) {
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null)
            ->with(['url' => $sources]);
        $this->browser
            ->expects($this->once())
            ->method('getHost')
            ->willReturn('http://example.com');
        // test
        $this->assertNull($this->widget->getCatalogItem($item));
    }

    /**
     * Test get catalog item
     *
     * @dataProvider getItemSources
     *
     * @param array $sources
     * @param array $item
     */
    public function testGetCatalogItem(array $sources, array $item) {
        $catalog_item = $this->getMock('\AnimeDb\Bundle\CatalogBundle\Entity\Item');
        $source = $this->getMock('\AnimeDb\Bundle\CatalogBundle\Entity\Source');
        $source
            ->expects($this->once())
            ->method('getItem')
            ->willReturn($catalog_item);
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($source)
            ->with(['url' => $sources]);
        $this->browser
            ->expects($this->once())
            ->method('getHost')
            ->willReturn('http://example.com');
        // test
        $this->assertEquals($catalog_item, $this->widget->getCatalogItem($item));
    }

    /**
     * Test get item
     */
    public function testGetItem()
    {
        $id = 123;
        $expected = ['foo', 'bar'];
        $this->browser
            ->expects($this->once())
            ->method('get')
            ->willReturn($expected)
            ->with(str_replace('#ID#', $id, Widget::PATH_ITEM_INFO));
        $this->assertEquals($expected, $this->widget->getItem($id));
    }
}
