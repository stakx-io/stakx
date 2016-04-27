<?php

namespace allejo\stakx\Twig;

use Twig_Environment;

class BaseUrlFunction
{
    public function __invoke (Twig_Environment $env, $assetPath)
    {
        $globals = $env->getGlobals();
        $baseURL = rtrim($globals['site']['base'], '/') . '/';
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