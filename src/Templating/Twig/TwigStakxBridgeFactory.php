<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use allejo\stakx\Compilation\Configuration;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Extension\DebugExtension;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Profile;

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
            catch (LoaderError $e)
            {
                $logger->error('The following theme could not be loaded: {theme}', [
                    'theme' => $theme,
                ]);
                $logger->error($e->getMessage());
            }
        }

        $twig = new Environment($loader, [
            'autoescape' => $configuration->getTwigAutoescape(),
            'auto_reload' => true,
            'cache' => fs::absolutePath('.stakx-cache/twig'),
        ]);

        $twig->addExtension($twigExtension);

        $profiler = null;

        if (Service::hasRunTimeFlag(RuntimeStatus::IN_PROFILE_MODE))
        {
            $profiler = new Profile();
            $twig->addExtension(new ProfilerExtension($profiler));
        }

        if ($configuration->isDebug())
        {
            $twig->addExtension(new DebugExtension());
            $twig->enableDebug();
        }

        $bridge = new TwigStakxBridge($twig);
        $bridge->setProfiler($profiler);

        return $bridge;
    }
}
