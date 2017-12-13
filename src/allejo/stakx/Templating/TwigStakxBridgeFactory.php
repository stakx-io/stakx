<?php

namespace allejo\stakx\Templating;

use allejo\stakx\Command\BuildableCommand;
use allejo\stakx\Configuration;
use allejo\stakx\Engines\Markdown\TwigMarkdownEngine;
use allejo\stakx\Service;
use allejo\stakx\System\Filesystem;
use allejo\stakx\Twig\FilesystemExtension;
use allejo\stakx\Twig\StakxTwigFileLoader;
use allejo\stakx\Twig\TextExtension;
use allejo\stakx\Twig\TwigExtension;
use Aptoma\Twig\Extension\MarkdownExtension;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Environment;
use Twig_Extension_Debug;

class TwigStakxBridgeFactory
{
    public static function createTwigEnvironment(ContainerInterface $container, LoggerInterface $logger)
    {
        $fs = new Filesystem();
        $loader = new StakxTwigFileLoader(array(
            getcwd(),
        ));

        /** @var Configuration $configuration */
        $configuration = $container->get(Configuration::class);
        $theme = $configuration->getTheme();
        $mdEngine = new TwigMarkdownEngine();

        // Only load a theme if one is specified and actually exists
        if ($theme !== null)
        {
            try
            {
                $loader->addPath($fs->absolutePath('_themes', $theme), 'theme');
            }
            catch (\Twig_Error_Loader $e)
            {
                $logger->error('The following theme could not be loaded: {theme}', array(
                    'theme' => $theme,
                ));
                $logger->error($e->getMessage());
            }
        }

        $twig = new Twig_Environment($loader, array(
            'autoescape'  => $configuration->getTwigAutoescape(),
            'auto_reload' => true,
            'cache'       => $fs->absolutePath('.stakx-cache/twig'),
        ));

        // We'll use this to access the current file in our Twig filters
        $twig->addGlobal('__currentTemplate', '');

        $twig->addExtension(new TwigExtension());
        $twig->addExtension(new TextExtension());
        $twig->addExtension(new MarkdownExtension($mdEngine));

        if (!Service::getParameter(BuildableCommand::SAFE_MODE))
        {
            $twig->addExtension(new FilesystemExtension());
        }

        if ($configuration->isDebug())
        {
            $twig->addExtension(new Twig_Extension_Debug());
            $twig->enableDebug();
        }

        return (new TwigStakxBridge($twig));
    }
}
