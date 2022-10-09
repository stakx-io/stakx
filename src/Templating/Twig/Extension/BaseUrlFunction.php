<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Service;
use Twig\TwigFunction;
use Twig_Environment;

class BaseUrlFunction extends AbstractTwigExtension implements TwigFunctionInterface
{
    private $site;

    public function __invoke(Twig_Environment $env, $assetPath, $absolute = false, $params = [])
    {
        if ($this->isExternalUrl($assetPath))
        {
            return $assetPath;
        }

        $globals = $env->getGlobals();
        $this->site = $globals['site'];

        $url = $this->getUrl($absolute);
        $baseURL = $this->getBaseUrl();
        $permalink = $this->guessAssetPath($assetPath, $params);

        return $this->buildPermalink($url, $baseURL, $permalink);
    }

    public static function get()
    {
        return new TwigFunction('url', new self(), [
            'needs_environment' => true,
        ]);
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

    private function guessAssetPath($assetPath, $params)
    {
        if ($assetPath instanceof JailedDocument && $assetPath->_coreInstanceOf(RepeaterPageView::class))
        {
            /** @var RepeaterPageView $assetPath */
            return ($link = $assetPath->_getPermalinkWhere($params)) ? $link : '/';
        }
        elseif (is_array($assetPath) || ($assetPath instanceof \ArrayAccess))
        {
            return (isset($assetPath['permalink'])) ? $assetPath['permalink'] : '/';
        }
        elseif (is_null($assetPath))
        {
            return '/';
        }
        elseif ($assetPath instanceof \SplFileInfo)
        {
            return str_replace(Service::getWorkingDirectory(), '', $assetPath);
        }

        return $assetPath;
    }

    /**
     * Match external URLs.
     *
     * If the string contains an `://`, then it's guessed to be an external URL.
     *
     * @param string|mixed $str
     *
     * @return bool True if the given string is guessed to be an external URL.
     */
    private function isExternalUrl($str)
    {
        if (!is_string($str))
        {
            return false;
        }

        return preg_match('#.+://.+#', $str) === 1;
    }

    /**
     * @see   https://stackoverflow.com/a/15575293
     *
     * @return string
     */
    private function buildPermalink()
    {
        $paths = [];

        foreach (func_get_args() as $arg)
        {
            if ($arg !== '')
            {
                $paths[] = $arg;
            }
        }

        return preg_replace('#(?<!:)/+#', '/', join('/', $paths));
    }
}
