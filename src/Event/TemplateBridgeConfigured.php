<?php

namespace allejo\stakx\Event;

use allejo\stakx\Templating\TemplateBridgeInterface;
use Symfony\Contracts\EventDispatcher\Event;

class TemplateBridgeConfigured extends Event
{
    public const NAME = 'templating.bridge.configured';

    public function __construct(private readonly TemplateBridgeInterface $templateBridge)
    {
    }

    public function getTemplateBridge(): TemplateBridgeInterface
    {
        return $this->templateBridge;
    }
}
