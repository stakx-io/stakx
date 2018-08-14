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
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use FastRoute\RouteCollector;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;

class RouteDispatcher
{
    /**
     * @internal
     */
    private function __construct()
    {
    }

    /**
     * Build a controller for handling a Static PageView's URL.
     *
     * @param StaticPageView $pageView
     * @param Compiler       $compiler
     *
     * @return \Closure
     */
    private function staticPageViewController(StaticPageView $pageView, Compiler $compiler)
    {
        return function () use ($pageView, $compiler) {
            $pageView->readContent();
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
     *
     * @param DynamicPageView $pageView
     * @param Compiler        $compiler
     *
     * @return \Closure
     */
    private function dynamicPageViewController(DynamicPageView $pageView, Compiler $compiler)
    {
        return function (ServerRequestInterface $request) use ($pageView, $compiler) {
            $contentItem = self::getContentItem($pageView, $request->getUri()->getPath());

            if ($contentItem === null)
            {
                return DevServer::return404();
            }

            $pageView->readContent();
            $contentItem->readContent();

            return DevServer::return200($compiler->renderDynamicPageView($pageView, $contentItem));
        };
    }

    /**
     * Return the appropriate controller based on a PageView's type.
     *
     * @param BasePageView|DynamicPageView|RepeaterPageView|StaticPageView $pageView
     * @param Compiler     $compiler
     *
     * @return \Closure
     */
    private function createController(BasePageView $pageView, Compiler $compiler)
    {
        switch ($pageView->getType())
        {
            case BasePageView::STATIC_TYPE:
                return $this->staticPageViewController($pageView, $compiler);

            case BasePageView::DYNAMIC_TYPE:
                return $this->dynamicPageViewController($pageView, $compiler);

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
            $dispatcher = new RouteDispatcher();

            foreach ($router->getRouteMapping() as $route => $pageView)
            {
                $r->get($route, $dispatcher->createController($pageView, $compiler));
            }
        });
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
}
