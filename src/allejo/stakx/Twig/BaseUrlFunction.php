<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

use Twig_Environment;

class BaseUrlFunction implements StakxTwigFilter
{
    private $site;

    public function __invoke(Twig_Environment $env, $assetPath, $absolute = false)
    {
        $globals = $env->getGlobals();
        $this->site = $globals['site'];

        $url = $this->getUrl($absolute);
        $baseURL = $this->getBaseUrl();
        $permalink = $this->guessAssetPath($assetPath);

        return $this->buildPermalink($url, $baseURL, $permalink);
    }

    public static function get()
    {
        return new \Twig_SimpleFunction('url', new self(), array(
            'needs_environment' => true,
        ));
    }

    private function getUrl($absolute)
    {
        $url = '/';

        if (!$absolute)
        {
            return $url;
        }

        if (isset($this->site['url']))
        {
            $url = $this->site['url'];
        }

        return ltrim($url, '/');
    }

    private function getBaseUrl()
    {
        $base = '';

        if (isset($this->site['baseurl']))
        {
            $base = $this->site['baseurl'];
        }
        elseif (isset($this->site['base']))
        {
            $base = $this->site['base'];
        }

        return $base;
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

    /**
     * @link   https://stackoverflow.com/a/15575293
     * @return string
     */
    private function buildPermalink()
    {
        $paths = array();

        foreach (func_get_args() as $arg)
        {
            if ($arg !== '')
            {
                $paths[] = $arg;
            }
        }

        return preg_replace('#(?<!:)/+#','/', join('/', $paths));
    }
}
