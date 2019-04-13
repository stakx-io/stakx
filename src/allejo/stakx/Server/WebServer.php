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
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

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
     * @param RouteMapper $routeMapper
     * @param Compiler    $compiler
     *
     * @return Server
     */
    public static function create(RouteMapper $routeMapper, Compiler $compiler)
    {
        $routes = Controller::create($routeMapper, $compiler);

        return new Server(function (ServerRequestInterface $request) use ($routes, $compiler) {
            $httpMethod = $request->getMethod();
            $urlPath = Controller::normalizeUrl($request->getUri()->getPath());

            // We're a static website, we should never support anything other than GET requests
            if ($httpMethod !== 'GET')
            {
                return new Response(406, ['Content-Type' => 'text/plain'], 'Method not allowed');
            }

            $context = new RequestContext($urlPath);
            $matcher = new UrlMatcher($routes, $context);

            try
            {
                $parameters = $matcher->match($urlPath);

                if (isset($parameters['_controller']))
                {
                    $controller = $parameters['_controller'];

                    return $controller($request, ...array_values($parameters));
                }
            }
            catch (ResourceNotFoundException $e)
            {
                if (($asset = self::searchAsset($urlPath)) !== null)
                {
                    return $asset;
                }
            }
            catch (\Exception $e)
            {
                $response = ExceptionRenderer::render($e, $compiler);

                return new Response(500, ['Content-Type' => 'text/html'], $response);
            }

            return WebServer::return404();
        });
    }

    /**
     * Search for a static asset for our DevServer to load.
     *
     * @param string $url
     * @param bool   $continueNesting
     *
     * @return Response|null
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
