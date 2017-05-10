<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

use Twig_Environment;

class BaseUrlFunction implements StakxTwigFilter
{
    public function __invoke(Twig_Environment $env, $assetPath)
    {
        $globals = $env->getGlobals();
        $assetPath = $this->guessAssetPath($assetPath);

        // @TODO 1.0.0 Remove support for 'base' as it's been deprecated
        $base = (array_key_exists('base', $globals['site'])) ? $globals['site']['base'] : $globals['site']['baseurl'];

        $baseURL = (empty($base)) ? '/' : '/' . trim($base, '/') . '/';
        $url = $this->trimSlashes($assetPath);

        return $baseURL . $url;
    }

    public static function get()
    {
        return new \Twig_SimpleFunction('url', new self(), array(
            'needs_environment' => true,
        ));
    }

    private function guessAssetPath($assetPath)
    {
        if (is_array($assetPath) || ($assetPath instanceof \ArrayAccess))
        {
            return (isset($assetPath['permalink'])) ? $assetPath['permalink'] : '/';
        }
        elseif (is_null($assetPath))
        {
            return '/';
        }

        return $assetPath;
    }

    private function trimSlashes($url)
    {
        $url = ltrim($url, '/');

        if (!empty($url) && $url[strlen($url) - 1] == '/')
        {
            return rtrim($url, '/') . '/';
        }

        return $url;
    }
}
