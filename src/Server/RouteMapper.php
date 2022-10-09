<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Server;

use __\__;
use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\FrontMatter\FrontMatterParser;

class RouteMapper
{
    /** @var string[] */
    private array $redirects;

    private string $baseUrl;

    /** @var BasePageView[] */
    private array $mapping;

    public function __construct()
    {
        $this->redirects = [];
        $this->mapping = [];
        $this->baseUrl = '';
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function setBaseUrl($baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Register a PageView to this router.
     */
    public function registerPageView(BasePageView $pageView): void
    {
        switch ($pageView->getType()) {
            case BasePageView::STATIC_TYPE:
                $this->mapping[$pageView->getPermalink()] = $pageView;

                foreach ($pageView->getRedirects() as $redirect) {
                    $this->redirects[$redirect] = $pageView->getPermalink();
                }

                break;

            case BasePageView::DYNAMIC_TYPE:
                $rawFM = $pageView->getRawFrontMatter();
                $permalinkFM = \__::get($rawFM, 'permalink');

                $permalink = is_array($permalinkFM) ? $permalinkFM[0] : $permalinkFM;
                $permalink = preg_replace(FrontMatterParser::VARIABLE_DEF, '{$1}', $permalink);

                $this->mapping[$permalink] = $pageView;

                break;

            case BasePageView::REPEATER_TYPE:
                $rawFM = $pageView->getRawFrontMatter();
                $permalinkFM = \__::get($rawFM, 'permalink');

                $permalink = is_array($permalinkFM) ? $permalinkFM[0] : $permalinkFM;
                $permalink = preg_replace(FrontMatterParser::VARIABLE_DEF, '{$1}', $permalink);
                $permalink = preg_replace(FrontMatterParser::ARRAY_DEF, '{$1}', $permalink);

                // Replace `.` in complex FM variables because they don't work in routes
                $permalink = preg_replace('/\./', '_', $permalink);

                $this->mapping[$permalink] = $pageView;

                break;
        }
    }

    /**
     * Get the PageView used for a specified route.
     *
     * @param string $route
     *
     * @return null|DynamicPageView|RepeaterPageView|StaticPageView
     */
    public function getRoute($route)
    {
        return __::get($this->mapping, $route);
    }

    /**
     * Retrieve all of the URL routes registered.
     *
     * @return string[]
     */
    public function getRoutes(): array
    {
        return array_keys($this->mapping);
    }

    public function &getRouteMapping()
    {
        ksort($this->mapping);

        return $this->mapping;
    }

    public function &getRedirectMapping()
    {
        ksort($this->redirects);

        return $this->redirects;
    }

    public static function extractUrlParameters($permalink)
    {
        $matches = [];

        preg_match_all(FrontMatterParser::ANY_VARIABLE, (string)$permalink, $matches);

        return $matches[1];
    }
}
