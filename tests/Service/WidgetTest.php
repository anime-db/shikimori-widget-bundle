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
        $this->repository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
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
                false,
                []
            ],
            [
                false,
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
     * @param integer|false $expected
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
