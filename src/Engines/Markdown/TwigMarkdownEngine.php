<?php

namespace allejo\stakx\Engines;

use Aptoma\Twig\Extension\MarkdownEngineInterface;

class TwigMarkdownEngine implements MarkdownEngineInterface
{
    protected $engine;

    public function __construct($instanceName = null)
    {
        $this->engine = MarkdownEngine::instance($instanceName);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($content)
    {
        return $this->engine->parse($content);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'stakx/parsedown';
    }
}