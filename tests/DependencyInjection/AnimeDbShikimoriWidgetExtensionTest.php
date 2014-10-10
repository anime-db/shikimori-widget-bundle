<?php
/**
 * AnimeDb package
*
* @package   AnimeDb
* @author    Peter Gribanov <info@peter-gribanov.ru>
* @copyright Copyright (c) 2011, Peter Gribanov
* @license   http://opensource.org/licenses/GPL-3.0 GPL v3
*/

namespace AnimeDb\Bundle\ShikimoriWidgetBundle\Tests\DependencyInjection;

use AnimeDb\Bundle\ShikimoriWidgetBundle\DependencyInjection\AnimeDbShikimoriWidgetExtension;

/**
 * Test DependencyInjection
 *
 * @package AnimeDb\Bundle\ShikimoriWidgetBundle\Tests\DependencyInjection
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class AnimeDbShikimoriWidgetExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test load
     */
    public function testLoad()
    {
        $di = new AnimeDbShikimoriWidgetExtension();
        $di->load([], $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder'));
    }
}
