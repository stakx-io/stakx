<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Document\StaticPageView;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MenuManager extends BaseManager
{
    private array $siteMenu = [];

    public function __construct(private readonly PageManager $pageManager, private readonly EventDispatcherInterface $eventDispatcher, private readonly LoggerInterface $logger)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function compileManager(): void
    {
        if ($this->pageManager === null) {
            return;
        }

        $this->buildFromPageViews($this->pageManager->getStaticPageViews());
    }

    /**
     * An array representing the website's menu structure with children and grandchildren made from static PageViews.
     *
     * @return JailedDocument[]
     */
    public function getSiteMenu(): array
    {
        $jailedMenu = [];

        foreach ($this->siteMenu as $key => $value) {
            // If it's an array, it means the parent is hidden from the site menu therefore its children should be too
            if (is_array($this->siteMenu[$key])) {
                continue;
            }

            $jailedMenu[$key] = $value->createJail();
        }

        return $jailedMenu;
    }

    /**
     * @param StaticPageView[] $pageViews
     */
    public function buildFromPageViews($pageViews): void
    {
        foreach ($pageViews as $pageView) {
            $this->addToSiteMenu($pageView);
        }
    }

    /**
     * @param StaticPageView $pageView
     */
    public function addToSiteMenu($pageView): void
    {
        $frontMatter = $pageView->getFrontMatter();

        if (isset($frontMatter['menu']) && !$frontMatter['menu']) {
            return;
        }

        $url = trim((string)$pageView->getPermalink(), '/');

        // @TODO in the next breaking release, remove this check and allow the homepage to be indexed as '.'
        if (empty($url)) {
            return;
        }

        $root = &$this->siteMenu;
        $dirs = explode('/', $url);

        while (count($dirs) > 0) {
            $name = array_shift($dirs);
            $name = (!empty($name)) ? $name : '.';

            if (!is_null($name) && count($dirs) == 0) {
                if (isset($root[$name]) && is_array($root[$name])) {
                    $children = &$pageView->getChildren();
                    $children = $root[$name]['children'];
                }

                $root[$name] = &$pageView;
            } else {
                if (!isset($root[$name])) {
                    $root[$name]['children'] = [];
                    $root = &$root[$name]['children'];
                } elseif (isset($root[$name]) && is_array($root[$name])) {
                    $root = &$root[$name]['children'];
                } else {
                    $root = &$root[$name]->getChildren();
                }
            }
        }
    }
}
