<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Server;

use allejo\stakx\Compiler;
use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\CollectableItem;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\ReadableDocument;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Document\TemplateReadyDocument;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\FrontMatter\ExpandedValue;
use allejo\stakx\Service;
use Closure;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Controller
{
    private static $baseUrl = '';

    private array $lastModified = [];

    /**
     * @internal
     */
    private function __construct()
    {
    }

    /**
     * Create a FastRoute Dispatcher.
     */
    public static function create(RouteMapper $routeMapper, Compiler $compiler): RouteCollection
    {
        self::$baseUrl = $routeMapper->getBaseUrl();

        $dispatcher = new Controller();
        $router = new RouteCollection();

        /**
         * @var string $from
         * @var string $to
         */
        foreach ($routeMapper->getRedirectMapping() as $from => $to) {
            $routeName = preg_replace('/[\/\.]/', '_', $from);
            $routeName = 'redirect_' . $routeName;

            $route = new Route($from, ['_controller' => $dispatcher->createRedirectAction($to)]);

            $router->add($routeName, $route);
        }

        /**
         * @var string       $routeUrl
         * @var BasePageView $pageView
         */
        foreach ($routeMapper->getRouteMapping() as $routeUrl => $pageView) {
            $routeName = $pageView->getRelativeFilePath();
            $routeName = preg_replace('/[\/\.]/', '_', $routeName);

            // Find the name of the last route parameter, if one exists
            $results = [];
            preg_match('/{(.+)}\/?$/', $routeUrl, $results);

            // Allow the last route parameter to have `/` in the permalink that's not part of the route itself
            //   see https://github.com/stakx-io/stakx/issues/98
            $requirements = [];
            if (count($results) >= 2) {
                $requirements[$results[1]] = '.*';
            }

            $route = new Route(
                $routeUrl,
                ['_controller' => $dispatcher->createAction($pageView, $compiler)],
                $requirements
            );

            $router->add($routeName, $route);
        }

        return $router;
    }

    /**
     * Normalize a given URL.
     *
     * A normalized URL is one with `baseurl` stripped away from it. This is necessary because all permalinks in stakx
     * are handled without the base so it's necessary to be able to reference correct correct permalinks.
     *
     * @param string $url
     */
    public static function normalizeUrl($url): mixed
    {
        return str_replace(self::$baseUrl, '/', $url);
    }

    /**
     * Build a controller for handling a Static PageView's URL.
     */
    private function staticPageViewAction(StaticPageView $pageView, Compiler $compiler): Closure
    {
        return function () use ($pageView, $compiler) {
            Service::setOption('currentTemplate', $pageView->getAbsoluteFilePath());

            $compiler->getTemplateBridge()->clearTemplateCache();

            if ($this->hasBeenTouched($pageView)) {
                $pageView->readContent();
            }

            $mimeType = MimeDetector::getMimeType(fs::getExtension($pageView->getTargetFile()));

            return new Response(
                200,
                ['Content-Type' => $mimeType],
                $compiler->renderStaticPageView($pageView)
            );
        };
    }

    /**
     * Build a controller for handling a Dynamic PageView's URL.
     */
    private function dynamicPageViewAction(DynamicPageView $pageView, Compiler $compiler): Closure
    {
        return function (ServerRequestInterface $request) use ($pageView, $compiler) {
            Service::setOption('currentTemplate', $pageView->getAbsoluteFilePath());

            $compiler->getTemplateBridge()->clearTemplateCache();

            $contentItem = self::getContentItem($pageView, $request->getUri()->getPath());

            if ($contentItem === null) {
                return WebServer::return404();
            }

            if ($this->hasBeenTouched($pageView)) {
                $pageView->readContent();
            }

            if ($this->hasBeenTouched($contentItem)) {
                $contentItem->readContent();
            }

            return WebServer::return200($compiler->renderDynamicPageView($pageView, $contentItem));
        };
    }

    /**
     * Build a controller for handling a Repeater PageView's URL.
     */
    private function repeaterPageViewAction(RepeaterPageView $pageView, Compiler $compiler): Closure
    {
        return function (ServerRequestInterface $request) use ($pageView, $compiler) {
            Service::setOption('currentTemplate', $pageView->getAbsoluteFilePath());

            $compiler->getTemplateBridge()->clearTemplateCache();

            $expandedValue = self::getExpandedValue($pageView, $request->getUri()->getPath());

            if ($expandedValue === null) {
                return WebServer::return404();
            }

            if ($this->hasBeenTouched($pageView)) {
                $pageView->readContent();
            }

            return WebServer::return200($compiler->renderRepeaterPageView($pageView, $expandedValue));
        };
    }

    /**
     * Return the appropriate action based on a PageView's type.
     */
    private function createAction(BasePageView|DynamicPageView|RepeaterPageView|StaticPageView $pageView, Compiler $compiler): Closure
    {
        return match ($pageView->getType()) {
            BasePageView::STATIC_TYPE => $this->staticPageViewAction($pageView, $compiler),
            BasePageView::DYNAMIC_TYPE => $this->dynamicPageViewAction($pageView, $compiler),
            BasePageView::REPEATER_TYPE => $this->repeaterPageViewAction($pageView, $compiler),
            default => function () {
                $errMsg = 'This URL type has not yet been implemented.';

                return new Response(501, ['Content-Type' => 'text/plain'], $errMsg);
            },
        };
    }

    /**
     * Create a redirect response to forward.
     *
     * @param string $to The destination URL
     */
    private function createRedirectAction($to): Closure
    {
        return fn () => new Response(302, [
            'Location' => $to,
        ]);
    }

    /**
     * Check to see if a file has been touched since we last read it.
     *
     * @return bool True if the file has been modified since it was last accessed
     */
    private function hasBeenTouched(ReadableDocument $document): bool
    {
        $rPath = $document->getRelativeFilePath();

        if (!isset($this->lastModified[$rPath])) {
            $this->lastModified[$rPath] = $document->getLastModified();

            return true;
        }

        return $document->getLastModified() > $this->lastModified[$rPath];
    }

    /**
     * Find a ContentItem from a Dynamic PageView or null if it doesn't exist.
     *
     * @return null|CollectableItem|ReadableDocument|TemplateReadyDocument
     */
    private static function getContentItem(DynamicPageView $pageView, mixed $permalink)
    {
        $permalink = self::normalizeUrl($permalink);

        foreach ($pageView->getCollectableItems() as $collectableItem) {
            if ($collectableItem['permalink'] === $permalink) {
                return $collectableItem;
            }
        }

        return null;
    }

    /**
     * Find the ExpandedValue from a Repeater PageView or null if it doesn't exist.
     */
    private static function getExpandedValue(RepeaterPageView $pageView, mixed $permalink): ?ExpandedValue
    {
        $url = self::normalizeUrl($permalink);
        $repeaterPermalinks = $pageView->getRepeaterPermalinks();

        foreach ($repeaterPermalinks as $expandedValue) {
            if ($expandedValue->getEvaluated() === $url) {
                return $expandedValue;
            }
        }

        return null;
    }
}
