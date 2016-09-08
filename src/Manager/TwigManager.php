<?php

namespace allejo\stakx\Manager;

use allejo\stakx\Engines\TwigMarkdownEngine;
use allejo\stakx\Object\Configuration;
use allejo\stakx\Twig\FilesystemExtension;
use allejo\stakx\Twig\TwigExtension;
use Aptoma\Twig\Extension\MarkdownExtension;
use Twig_Environment;
use Twig_Loader_Filesystem;

class TwigManager extends BaseManager
{
    /**
     * @var Twig_Environment
     */
    private static $twig;

    public static function &getInstance ()
    {
        return self::$twig;
    }

    /**
     * @param Configuration $configuration
     * @param mixed         $options
     */
    public function configureTwig ($configuration, $options = array())
    {
        $loader   = new Twig_Loader_Filesystem(array(
            getcwd()
        ));
        $theme    = $configuration->getTheme();
        $mdEngine = new TwigMarkdownEngine();

        // Only load a theme if one is specified and actually exists
        if (!is_null($theme))
        {
            try
            {
                $loader->addPath($this->fs->absolutePath('_themes', $configuration->getTheme()), 'theme');
            }
            catch (\Twig_Error_Loader $e)
            {
                $this->output->error("The following theme could not be loaded: {theme}", array(
                    "theme" => $theme
                ));
                $this->output->error($e->getMessage());
            }
        }

        self::$twig = new Twig_Environment($loader, array(
            'autoescape'  => $configuration->getTwigAutoescape(),
            'auto_reload' => true,
            'cache'      => '.stakx-cache/twig'
        ));

        foreach ($options['globals'] as $global)
        {
            self::$twig->addGlobal($global['name'], $global['value']);
        }

        // We'll use this to access the current file in our Twig filters
        self::$twig->addGlobal('__currentTemplate', '');

        self::$twig->addExtension(new TwigExtension());
        self::$twig->addExtension(new \Twig_Extensions_Extension_Text());
        self::$twig->addExtension(new MarkdownExtension($mdEngine));

        if (!$options['safe'])
        {
            self::$twig->addExtension(new FilesystemExtension());
        }

        if ($configuration->isDebug())
        {
            self::$twig->addExtension(new \Twig_Extension_Debug());
            self::$twig->enableDebug();
        }
    }
}