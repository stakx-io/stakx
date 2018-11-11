<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use allejo\stakx\Compiler;
use allejo\stakx\Templating\TemplateBridgeInterface;
use Psr\Log\LoggerInterface;

class TwigStakxBridge implements TemplateBridgeInterface
{
    private $twig;
    private $logger;

    /** @var \Twig_Profiler_Profile */
    private $profiler;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setGlobalVariable($key, $value)
    {
        $this->twig->addGlobal($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function clearTemplateCache()
    {
        $this->twig->clearTemplateCache();
    }

    /**
     * {@inheritdoc}
     */
    public function createTemplate($templateContent)
    {
        try
        {
            $template = $this->twig->createTemplate($templateContent);
        }
        catch (\Twig_Error $e)
        {
            throw new TwigError($e);
        }

        return new TwigTemplate($template);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssortmentDependencies($namespace, $bodyContent)
    {
        // To see what this regex should match and what shouldn't be see:
        //     tests/allejo/stakx/Test/FrontMatter/FrontMatterDocumentTest.php

        $regex = "/{[{%].*?(?:$namespace)(?:\.|\[['\"])?([^_][^\W]+)?(?:\.|['\"]\])?[^_=]*?[%}]}/";
        $results = [];

        preg_match_all($regex, $bodyContent, $results);

        return array_unique($results[1]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateImportDependencies($bodyContent)
    {
        $regex = "/{%\s?(?:import|from|include)\s?['\"](.+)['\"].+/";
        $results = [];

        preg_match_all($regex, $bodyContent, $results);

        if (empty($results[1]))
        {
            return [];
        }

        return array_unique($results[1]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasProfiler()
    {
        return $this->profiler !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function setProfiler($profiler)
    {
        $this->profiler = $profiler;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilerOutput(Compiler $compiler)
    {
        $dumper = new TwigTextProfiler();
        $dumper->setTemplateMappings($compiler->getTemplateMappings());

        return $dumper->dump($this->profiler);
    }
}
