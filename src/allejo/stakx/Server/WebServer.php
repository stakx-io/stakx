<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Server;

use allejo\stakx\Compiler;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FilesystemPath;
use allejo\stakx\Service;
use FastRoute\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use React\Http\Server;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class WebServer
{
    /**
     * Return a 200 HTML Response.
     *
     * This is just a utility function available.
     *
     * @param string $content
     * @param mixed  $mimeType
     *
     * @return Response
     */
    public static function return200($content, $mimeType = 'text/html')
    {
        return new Response(200, ['Content-Type' => $mimeType], $content);
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
     * Factory method for creating a React Server instance.
     *
     * @param RouteMapper $router
     * @param Compiler    $compiler
     *
     * @return Server
     */
    public static function create(RouteMapper $router, Compiler $compiler)
    {
        $dispatcher = Controller::create($router, $compiler);

        return new Server(function (ServerRequestInterface $request) use ($router, $dispatcher) {
            $httpMethod = $request->getMethod();
            $urlPath = Controller::normalizeUrl($request->getUri()->getPath());

            // We're a static website, we should never support anything other than GET requests
            if ($httpMethod !== 'GET')
            {
                return new Response(406, ['Content-Type' => 'text/plain'], 'Method not allowed');
            }

            $routeInfo = $dispatcher->dispatch($httpMethod, $urlPath);

            switch ($routeInfo[0])
            {
                // We found a known URL meaning it's a PageView
                case Dispatcher::FOUND:
                    $urlPlaceholders = isset($routeInfo[2]) ? $routeInfo[2] : [];

                    return $routeInfo[1]($request, ...array_values($urlPlaceholders));

                case Dispatcher::NOT_FOUND:
                    if (($asset = self::searchAsset($urlPath)) !== null)
                    {
                        return $asset;
                    }

                    return WebServer::return404();

                default:
                    return WebServer::return404();
            }
        });
    }

    /**
     * Search for a static asset for our DevServer to load.
     *
     * @param string $url
     * @param bool   $continueNesting
     *
     * @return null|Response
     */
    private static function searchAsset($url, $continueNesting = true)
    {
        $preparedPath = substr($url, 0, 1) === '/' ? substr($url, 1) : $url;
        $path = new FilesystemPath($preparedPath);

        try
        {
            $file = new File($path);
            $mime = MimeDetector::getMimeType($file->getExtension());

            return new Response(200, ['Content-Type' => $mime], $file->getContents());
        }
        catch (FileNotFoundException $e)
        {
            if (!$continueNesting)
            {
                // This will evaluate to true if we've searched inside the themes folder and it still doesn't exist
                return null;
            }

            // If the asset doesn't exist within our project root, see if it exists in our theme folder
            $theme = Service::getOption('theme');
            $themeFile = "_themes/${theme}/" . $url;

            return self::searchAsset($themeFile, false);
        }
    }
}
