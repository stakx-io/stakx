<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Server;

use allejo\stakx\Compiler;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FilesystemPath;
use allejo\stakx\Manager\AssetManager;
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
     * @param RouteMapper  $routeMapper
     * @param Compiler     $compiler
     * @param AssetManager $assetManager
     *
     * @return Server
     */
    public static function create(RouteMapper $routeMapper, Compiler $compiler, AssetManager $assetManager)
    {
        $routes = Controller::create($routeMapper, $compiler);

        return new Server(function (ServerRequestInterface $request) use ($routes, $compiler, $assetManager) {
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
                // If we have a "manual" asset, let's serve from it
                if (($file = $assetManager->getExplicitAsset(self::normalizePath($urlPath))) !== null)
                {
                    return self::makeResponse($file);
                }

                // Our AssetManager only populates its registry of assets when files are copied at build time. Because
                // the web server doesn't perform the full site compilation, our manager is not populated. For this
                // reason, we manually look through the filesystem and load from there.
                //
                // @TODO this should be fixed to make the AssetManager the authority on assets.
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
        try
        {
            $file = new File(self::normalizePath($url));

            return self::makeResponse($file);
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

    /**
     * Given a URL, normalize it to how we maintain permalinks internally (without the preceding slash).
     *
     * @param string $url
     *
     * @return string
     */
    private static function normalizePath($url)
    {
        return substr($url, 0, 1) === '/' ? substr($url, 1) : $url;
    }

    /**
     * Given a File object, create a web server Response object for it.
     *
     * @param File $file
     *
     * @return Response
     */
    private static function makeResponse(File $file)
    {
        $mime = MimeDetector::getMimeType($file->getExtension());

        return new Response(200, ['Content-Type' => $mime], $file->getContents());
    }
}
