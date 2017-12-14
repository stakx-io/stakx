<?php

namespace allejo\stakx\Templating;

use allejo\stakx\Compiler;
use allejo\stakx\Twig\StakxTwigTextProfiler;
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

        return (new TwigTemplate($template));
    }

    /**
     * {@inheritdoc}
     */
    public function hasProfiler()
    {
        return ($this->profiler !== null);
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
        $dumper = new StakxTwigTextProfiler();
        $dumper->setTemplateMappings($compiler->getTemplateMappings());

        return $dumper->dump($this->profiler);
    }
}
