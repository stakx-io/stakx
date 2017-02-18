<?php

namespace allejo\stakx\Twig;

use Twig_Environment;

class BaseUrlFunction
{
    public function __invoke (Twig_Environment $env, $assetPath)
    {
        $globals = $env->getGlobals();

        if (is_array($assetPath) || ($assetPath instanceof \ArrayAccess))
        {
            $assetPath = (isset($assetPath['permalink'])) ? $assetPath['permalink'] : '/';
        }
        else if (is_null($assetPath))
        {
            $assetPath = '/';
        }

        // @TODO 1.0.0 Remove support for 'base' as it's been deprecated
        $base = (array_key_exists('base', $globals['site'])) ? $globals['site']['base'] : $globals['site']['baseurl'];

        $baseURL = (empty($base)) ? '/' : '/' . trim($base, '/') . '/';
        $url     = ltrim($assetPath, '/');

        // Sanity check, remove any excess trailing '/'
        if (!empty($url) && $url[strlen($url) - 1] == '/')
        {
            $url = rtrim($url, '/') . '/';
        }

        return ($baseURL . $url);
    }

    public static function get ()
    {
        return new \Twig_SimpleFunction('url', new self(), array(
            'needs_environment' => true
        ));
    }
}