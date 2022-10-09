<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use allejo\stakx\Compiler;
use allejo\stakx\Templating\TemplateBridgeInterface;
use allejo\stakx\Templating\TemplateInterface;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Profiler\Profile;

class TwigStakxBridge implements TemplateBridgeInterface
{
    private ?LoggerInterface $logger;

    private ?Profile $profiler;

    public function __construct(private readonly Environment $twig)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setGlobalVariable($key, $value): void
    {
        $this->twig->addGlobal($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function clearTemplateCache(): void
    {
        $this->twig->clearTemplateCache();
    }

    /**
     * {@inheritdoc}
     */
    public function createTemplate($templateContent): TemplateInterface
    {
        try {
            $template = $this->twig->createTemplate($templateContent);
        } catch (Error $e) {
            throw new TwigError($e);
        }

        return new TwigTemplate($template);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssortmentDependencies($namespace, $bodyContent): array
    {
        // To see what this regex should match and what shouldn't be see:
        //     tests/allejo/stakx/Test/FrontMatter/FrontMatterDocumentTest.php

        $regex = "/{[{%].*?(?:{$namespace})(?:\\.|\\[['\"])?([^_][^\\W]+)?(?:\\.|['\"]\\])?[^_=]*?[%}]}/";
        $results = [];

        preg_match_all($regex, $bodyContent, $results);

        return array_unique($results[1]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateImportDependencies($bodyContent): array
    {
        $regex = "/{%\\s?(?:import|from|include)\\s?['\"](.+)['\"].+/";
        $results = [];

        preg_match_all($regex, $bodyContent, $results);

        if (empty($results[1])) {
            return [];
        }

        return array_unique($results[1]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasProfiler(): bool
    {
        return $this->profiler !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function setProfiler($profiler): void
    {
        $this->profiler = $profiler;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilerOutput(Compiler $compiler): string
    {
        $dumper = new TwigTextProfiler();
        $dumper->setTemplateMappings($compiler->getTemplateMappings());

        return $dumper->dump($this->profiler);
    }
}
