<?php declare(strict_types=1);

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
    private readonly RstImageDirective $imageDirective;
    private readonly Parser $rstParser;

    public function __construct(AssetManager $assetManager)
    {
        $this->imageDirective = new RstImageDirective();
        $this->imageDirective->setAssetManager($assetManager);

        $this->rstParser = new Parser();
        $this->rstParser->registerDirective($this->imageDirective);
        $this->rstParser->registerDirective(new RstSyntaxBlock());
        $this->rstParser->setIncludePolicy(true, Service::getWorkingDirectory());
    }

    public function parse(string $content, ?ContentItem $contentItem = null): string
    {
        $this->imageDirective->setContentItem($contentItem);

        return $this->rstParser->parse($content)->render();
    }

    public function getTemplateTag(): string
    {
        return 'rst';
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions(): array
    {
        return [
            'rst',
        ];
    }
}
