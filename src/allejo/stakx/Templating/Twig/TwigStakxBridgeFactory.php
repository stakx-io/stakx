<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use allejo\stakx\Configuration;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use Psr\Log\LoggerInterface;
use Twig_Environment;
use Twig_Extension_Debug;

class TwigStakxBridgeFactory
{
    public static function createTwigEnvironment(
        Configuration $configuration,
        TwigExtension $twigExtension,
        LoggerInterface $logger
    ) {
        $loader = new TwigFileLoader([
            Service::getWorkingDirectory(),
        ]);

        $theme = $configuration->getTheme();

        // Only load a theme if one is specified and actually exists
        if ($theme !== null)
        {
            try
            {
                $loader->addPath(fs::absolutePath('_themes', $theme), 'theme');
            }
            catch (\Twig_Error_Loader $e)
            {
                $logger->error('The following theme could not be loaded: {theme}', [
                    'theme' => $theme,
                ]);
                $logger->error($e->getMessage());
            }
        }

        $twig = new Twig_Environment($loader, [
            'autoescape' => $configuration->getTwigAutoescape(),
            'auto_reload' => true,
            'cache' => fs::absolutePath('.stakx-cache/twig'),
        ]);

        $twig->addExtension($twigExtension);

        $profiler = null;

        if (Service::hasRunTimeFlag(RuntimeStatus::IN_PROFILE_MODE))
        {
            $profiler = new \Twig_Profiler_Profile();
            $twig->addExtension(new \Twig_Extension_Profiler($profiler));
        }

        if ($configuration->isDebug())
        {
            $twig->addExtension(new Twig_Extension_Debug());
            $twig->enableDebug();
        }

        $bridge = new TwigStakxBridge($twig);
        $bridge->setProfiler($profiler);

        return $bridge;
    }
}
