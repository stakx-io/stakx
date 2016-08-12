<?php

namespace allejo\stakx\Twig;

use Twig_Environment;

class BaseUrlFunction
{
    public function __invoke (Twig_Environment $env, $assetPath)
    {
        $globals = $env->getGlobals();

        // @todo Remove support for 'base' as it's been deprecated
        $base = (array_key_exists('base', $globals['site'])) ? $globals['site']['base'] : $globals['site']['baseurl'];

        $baseURL = rtrim($base, '/') . '/';
        $url     = ltrim($assetPath, '/');

        return ($baseURL . $url);
    }

    public static function get ()
    {
        return new \Twig_SimpleFunction('url', new self(), array(
            'needs_environment' => true
        ));
    }
}