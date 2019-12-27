<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Markup\RstSyntaxBlock;
use allejo\stakx\Service;
use Gregwar\RST\Parser;

class RstEngine extends Parser implements MarkupEngineInterface
{
    /** @var ContentItem|null */
    private $parentItem;

    public function __construct($environment = null, $kernel = null)
    {
        parent::__construct($environment, $kernel);

        $this->registerDirective(new RstSyntaxBlock());
        $this->setIncludePolicy(true, Service::getWorkingDirectory());
    }

    public function parse($content, $parentItem = null)
    {
        $this->parentItem = $parentItem;

        return parent::parse($content);
    }

    public function getTemplateTag()
    {
        return 'rst';
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            'rst',
        ];
    }
}
