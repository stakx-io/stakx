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
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use React\Http\Server;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class DevServer
{
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
                return null;
            }

            $theme = Service::getOption('theme');
            $themeFile = "_themes/${theme}/" . $url;

            return self::searchAsset($themeFile, false);
        }
    }

    public static function create(PageViewRouter $router, Compiler $compiler)
    {
        $dispatcher = RouteDispatcher::create($router, $compiler);

        return new Server(function (ServerRequestInterface $request) use ($router, $dispatcher) {
            $httpMethod = $request->getMethod();
            $urlPath = RouteDispatcher::normalizeUrl($request->getUri()->getPath());

            if ($httpMethod !== 'GET')
            {
                return new Response(406, ['Content-Type' => 'text/plain'], 'Method not allowed');
            }

            $routeInfo = $dispatcher->dispatch($httpMethod, $urlPath);

            switch ($routeInfo[0])
            {
                case \FastRoute\Dispatcher::FOUND:
                    $params = isset($routeInfo[2]) ? $routeInfo[2] : [];
                    return $routeInfo[1]($request, ...array_values($params));

                case \FastRoute\Dispatcher::NOT_FOUND:
                    $response = DevServer::return404();
                    $guessFile = self::searchAsset($urlPath);

                    if ($guessFile !== null)
                    {
                        $response = $guessFile;
                    }

                    return $response;

                default:
                    return DevServer::return404();
            }
        });
    }

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
}
