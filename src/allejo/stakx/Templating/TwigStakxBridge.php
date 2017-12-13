<?php

namespace allejo\stakx\Templating;

use Psr\Log\LoggerInterface;

class TwigStakxBridge implements TemplateBridgeInterface
{
    private $twig;
    private $logger;

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
}
