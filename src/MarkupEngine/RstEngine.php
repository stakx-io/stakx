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
use Doctrine\RST\Parser;

class RstEngine implements MarkupEngineInterface
{
    private Parser $parser;
    private RstImageDirective $imageDirective;

    public function __construct(AssetManager $assetManager)
    {
        $this->imageDirective = new RstImageDirective();
        $this->imageDirective->setAssetManager($assetManager);

        $this->parser = new Parser();
        $this->parser->registerDirective($this->imageDirective);
        $this->parser->registerDirective(new RstSyntaxBlock());
        $this->parser->setIncludePolicy(true, Service::getWorkingDirectory());
    }

    public function parse(string $content, ContentItem $contentItem = null): string
    {
        $this->imageDirective->setContentItem($contentItem);

        return $this->parser->parse($content)->render();
    }

    public function getTemplateTag(): ?string
    {
        return 'rst';
    }

    public function getExtensions(): array
    {
        return [
            'rst',
        ];
    }
}
