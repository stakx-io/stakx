<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Service;
use ArrayAccess;
use SplFileInfo;
use Twig\Environment;
use Twig\TwigFunction;

class BaseUrlFunction extends AbstractTwigExtension implements TwigFunctionInterface
{
    private array $site;

    public function __invoke(Environment $env, $assetPath, $absolute = false, $params = []): string
    {
        if ($this->isExternalUrl($assetPath)) {
            return $assetPath;
        }

        $globals = $env->getGlobals();
        $this->site = $globals['site'];

        $url = $this->getUrl($absolute);
        $baseURL = $this->getBaseUrl();
        $permalink = $this->guessAssetPath($assetPath, $params);

        return $this->buildPermalink($url, $baseURL, $permalink);
    }

    public static function get(): TwigFunction
    {
        return new TwigFunction('url', new self(), [
            'needs_environment' => true,
        ]);
    }

    private function getUrl($absolute): string
    {
        $url = '/';

        if (!$absolute) {
            return $url;
        }

        if (isset($this->site['url'])) {
            $url = $this->site['url'];
        }

        return ltrim((string)$url, '/');
    }

    private function getBaseUrl(): string
    {
        $base = '';

        if (isset($this->site['baseurl'])) {
            $base = $this->site['baseurl'];
        } elseif (isset($this->site['base'])) {
            $base = $this->site['base'];
        }

        return $base;
    }

    private function guessAssetPath($assetPath, $params): string
    {
        if ($assetPath instanceof JailedDocument && $assetPath->_coreInstanceOf(RepeaterPageView::class)) {
            return ($link = $assetPath->_getPermalinkWhere($params)) ? $link : '/';
        }
        if (is_array($assetPath) || ($assetPath instanceof ArrayAccess)) {
            return $assetPath['permalink'] ?? '/';
        }
        if (is_null($assetPath)) {
            return '/';
        }
        if ($assetPath instanceof SplFileInfo) {
            return str_replace(Service::getWorkingDirectory(), '', $assetPath);
        }

        return $assetPath;
    }

    /**
     * Match external URLs.
     *
     * If the string contains an `://`, then it's guessed to be an external URL.
     *
     * @param mixed|string $str
     *
     * @return bool true if the given string is guessed to be an external URL
     */
    private function isExternalUrl(mixed $str): bool
    {
        if (!is_string($str)) {
            return false;
        }

        return preg_match('#.+://.+#', $str) === 1;
    }

    /**
     * @see   https://stackoverflow.com/a/15575293
     */
    private function buildPermalink(): string
    {
        $paths = [];

        foreach (func_get_args() as $arg) {
            if ($arg !== '') {
                $paths[] = $arg;
            }
        }

        return preg_replace('#(?<!:)/+#', '/', implode('/', $paths));
    }
}
