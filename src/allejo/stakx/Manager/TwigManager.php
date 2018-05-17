<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Configuration;
use allejo\stakx\Engines\Markdown\TwigMarkdownEngine;
use allejo\stakx\Templating\Twig\TwigFileLoader;
use allejo\stakx\Twig\FilesystemExtension;
use allejo\stakx\Twig\TextExtension;
use allejo\stakx\Twig\TwigExtension;
use Aptoma\Twig\Extension\MarkdownExtension;
use Twig_Environment;

class TwigManager extends BaseManager
{
    /**
     * @var Twig_Environment
     */
    private static $twig;

    public static function &getInstance()
    {
        return self::$twig;
    }

    /**
     * @param Configuration $configuration
     * @param mixed         $options
     */
    public function configureTwig($configuration, $options = [])
    {
        $loader = new TwigFileLoader([
            getcwd(),
        ]);
        $theme = $configuration->getTheme();
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
                $this->output->error('The following theme could not be loaded: {theme}', [
                    'theme' => $theme,
                ]);
                $this->output->error($e->getMessage());
            }
        }

        self::$twig = new Twig_Environment($loader, [
            'autoescape' => $configuration->getTwigAutoescape(),
            'auto_reload' => true,
            'cache' => $this->fs->absolutePath('.stakx-cache/twig'),
        ]);

        foreach ($options['globals'] as $global)
        {
            self::$twig->addGlobal($global['name'], $global['value']);
        }

        // We'll use this to access the current file in our Twig filters
        self::$twig->addGlobal('__currentTemplate', '');

        self::$twig->addExtension(new TwigExtension());
        self::$twig->addExtension(new TextExtension());
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
