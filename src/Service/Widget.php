<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\ShikimoriWidgetBundle\Service;

use AnimeDb\Bundle\ShikimoriBrowserBundle\Service\Browser;
use AnimeDb\Bundle\ShikimoriFillerBundle\Service\Filler;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use AnimeDb\Bundle\CatalogBundle\Entity\Item;
use AnimeDb\Bundle\CatalogBundle\Entity\Source;
use AnimeDb\Bundle\CatalogBundle\Entity\Widget\Item as ItemWidget;

/**
 * Bundle
 *
 * @package AnimeDb\Bundle\ShikimoriWidgetBundle\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Widget
{
    /**
     * API path for get item info
     *
     * @var string
     */
    const PATH_ITEM_INFO = '/animes/#ID#';

    /**
     * RegExp for get item id
     *
     * @var string
     */
    const REG_ITEM_ID = '#/animes/(?<id>\d+)\-#';

    /**
     * World-art item url
     *
     * @var string
     */
    const WORLD_ART_URL = 'http://www.world-art.ru/animation/animation.php?id=#ID#';

    /**
     * MyAnimeList item url
     *
     * @var string
     */
    const MY_ANIME_LIST_URL = 'http://myanimelist.net/anime/#ID#';

    /**
     * AniDB item url
     *
     * @var string
     */
    const ANI_DB_URL = 'http://anidb.net/perl-bin/animedb.pl?show=anime&aid=#ID#';

    /**
     * Browser
     *
     * @var \AnimeDb\Bundle\ShikimoriBrowserBundle\Service\Browser
     */
    protected $browser;

    /**
     * Filler
     *
     * @var \AnimeDb\Bundle\ShikimoriFillerBundle\Service\Filler
     */
    protected $filler;

    /**
     * Repository
     *
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $repository;

    /**
     * Locale
     *
     * @var string
     */
    protected $locale;

    /**
     * Construct
     *
     * @param \AnimeDb\Bundle\ShikimoriBrowserBundle\Service\Browser $browser
     * @param \Doctrine\Bundle\DoctrineBundle\Registry $doctrine
     * @param string $locale
     */
    public function __construct(Browser $browser, Doctrine $doctrine, $locale)
    {
        $this->locale = substr($locale, 0, 2);
        $this->browser = $browser;
        $this->repository = $doctrine->getRepository('AnimeDbCatalogBundle:Source');
    }

    /**
     * Set filler
     *
     * @param \AnimeDb\Bundle\ShikimoriFillerBundle\Service\Filler|null $filler
     */
    public function setFiller(Filler $filler = null) {
        $this->filler = $filler;
    }

    /**
     * Get Shikimori item id from sources
     *
     * @param \AnimeDb\Bundle\CatalogBundle\Entity\Item $item
     *
     * @return integer|false
     */
    public function getItemId(Item $item)
    {
        /* @var $source \AnimeDb\Bundle\CatalogBundle\Entity\Source */
        foreach ($item->getSources() as $source) {
            if (strpos($source->getUrl(), $this->browser->getHost()) === 0 &&
                preg_match(self::REG_ITEM_ID, $source->getUrl(), $match)
            ) {
                return $match['id'];
            }
        }
        return false;
    }

    /**
     * Get hash from list items
     *
     * @param array $list
     *
     * @return string
     */
    public function hash(array $list)
    {
        $ids = '';
        foreach ($list as $item) {
            $ids .= ':'.$item['id'];
        }
        return md5($ids);
    }

    /**
     * Get widget item
     *
     * @param array $item
     *
     * @return \AnimeDb\Bundle\CatalogBundle\Entity\Widget\Item
     */
    public function getWidgetItem(array $item) {
        $entity = new ItemWidget();

        // set name
        if ($this->locale == 'ru' && $item['russian']) {
            $entity->setName($item['russian']);
        } elseif ($this->locale == 'ja' && $item['japanese']) {
            $entity->setName($item['japanese'][0]);
        } elseif ($this->locale == 'en' && $item['english']) {
            $entity->setName($item['english'][0]);
        } else {
            $entity->setName($item['name']);
        }
        $entity->setLink($this->browser->getHost().$item['url']);
        $entity->setCover($this->browser->getHost().$item['image']['original']);

        // find item by sources
        $sources = [$entity->getLink()];
        if (!empty($item['world_art_id'])) {
            $sources[] = str_replace('#ID#', $item['world_art_id'], self::WORLD_ART_URL);
        }
        if (!empty($item['myanimelist_id'])) {
            $sources[] = str_replace('#ID#', $item['myanimelist_id'], self::MY_ANIME_LIST_URL);
        }
        if (!empty($item['ani_db_id'])) {
            $sources[] = str_replace('#ID#', $item['ani_db_id'], self::ANI_DB_URL);
        }
        /* @var $source \AnimeDb\Bundle\CatalogBundle\Entity\Source|null */
        $source = $this->repository->findOneBy(['url' => $sources]);

        if ($source instanceof Source) {
            $entity->setItem($source->getItem());
        } elseif ($this->filler instanceof Filler) {
            $entity->setLinkForFill($this->filler->getLinkForFill($this->browser->getHost().$item['url']));
        }

        return $entity;
    }

    /**
     * Get item info
     *
     * @param integer $id
     *
     * @return array
     */
    public function getItem($id)
    {
        return $this->browser->get(str_replace('#ID#', $id, self::PATH_ITEM_INFO));
    }
}