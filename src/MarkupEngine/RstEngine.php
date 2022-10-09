<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Manager\AssetManager;
use allejo\stakx\Markup\RstImageDirective;
use allejo\stakx\Markup\RstSyntaxBlock;
use allejo\stakx\Service;
use Gregwar\RST\Parser;

class RstEngine extends Parser implements MarkupEngineInterface
{
    /** @var RstImageDirective */
    private $imageDirective;

    public function __construct(AssetManager $assetManager, $environment = null, $kernel = null)
    {
        parent::__construct($environment, $kernel);

        $this->imageDirective = new RstImageDirective();
        $this->imageDirective->setAssetManager($assetManager);

        $this->registerDirective($this->imageDirective);
        $this->registerDirective(new RstSyntaxBlock());
        $this->setIncludePolicy(true, Service::getWorkingDirectory());
    }

    public function parse($content, $contentItem = null)
    {
        $this->imageDirective->setContentItem($contentItem);

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
