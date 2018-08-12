<?php

namespace allejo\stakx\Server;

use allejo\stakx\Compiler;
use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\CollectableItem;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\ReadableDocument;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Document\TemplateReadyDocument;
use FastRoute\RouteCollector;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;

class RouteDispatcher
{
    /**
     * Return a 200 HTML Response.
     *
     * This is just a utility function available.
     *
     * @param string $content
     *
     * @return Response
     */
    public static function return200($content)
    {
        return new Response(200, ['Content-Type' => 'text/html'], $content);
    }

    /**
     * Return a 404 Response.
     *
     * This is just a utility function available.
     *
     * @return Response
     */
    public static function return404()
    {
        return new Response(404, ['Content-Type' => 'text/plain'], '404: Page not found');
    }

    /**
     * Build a controller for handling a Static PageView's URL.
     *
     * @param StaticPageView $pageView
     * @param Compiler       $compiler
     *
     * @return \Closure
     */
    private static function staticPageViewController(StaticPageView $pageView, Compiler $compiler)
    {
        return function () use ($pageView, $compiler) {
            $pageView->readContent();

            return self::return200($compiler->renderStaticPageView($pageView));
        };
    }

    /**
     * Build a controller for handling a Dynamic PageView's URL.
     *
     * @param DynamicPageView $pageView
     * @param Compiler        $compiler
     *
     * @return \Closure
     */
    private static function dynamicPageViewController(DynamicPageView $pageView, Compiler $compiler)
    {
        return function (ServerRequestInterface $request) use ($pageView, $compiler) {
            $contentItem = self::getContentItem($pageView, $request->getUri()->getPath());

            if ($contentItem === null)
            {
                return self::return404();
            }

            $pageView->readContent();
            $contentItem->readContent();

            return self::return200($compiler->renderDynamicPageView($pageView, $contentItem));
        };
    }

    /**
     * Find a ContentItem from a Dynamic PageView or null if it doesn't exist.
     *
     * @param DynamicPageView $pageView
     * @param                 $permalink
     *
     * @return CollectableItem|ReadableDocument|TemplateReadyDocument|null
     */
    private static function getContentItem(DynamicPageView $pageView, $permalink)
    {
        foreach ($pageView->getCollectableItems() as $collectableItem)
        {
            if ($collectableItem['permalink'] === $permalink)
            {
                return $collectableItem;
            }
        }

        return null;
    }

    /**
     * Return the appropriate controller based on a PageView's type.
     *
     * @param BasePageView|DynamicPageView|RepeaterPageView|StaticPageView $pageView
     * @param Compiler     $compiler
     *
     * @return \Closure
     */
    private static function createController(BasePageView $pageView, Compiler $compiler)
    {
        switch ($pageView->getType())
        {
            case BasePageView::STATIC_TYPE:
                return self::staticPageViewController($pageView, $compiler);

            case BasePageView::DYNAMIC_TYPE:
                return self::dynamicPageViewController($pageView, $compiler);

            default:
                return function () {
                    $errMsg = 'This URL type has not yet been implemented.';

                    return new Response(501, ['Content-Type' => 'text/plain'], $errMsg);
                };
        }
    }

    /**
     * Create a FastRoute Dispatcher.
     *
     * @param PageViewRouter $router
     * @param Compiler       $compiler
     *
     * @return \FastRoute\Dispatcher
     */
    public static function create(PageViewRouter $router, Compiler $compiler)
    {
        return \FastRoute\simpleDispatcher(function (RouteCollector $r) use ($router, $compiler) {
            foreach ($router->getRouteMapping() as $route => $pageView)
            {
                $r->get($route, self::createController($pageView, $compiler));
            }
        });
    }
}
