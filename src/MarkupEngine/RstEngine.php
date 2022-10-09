<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

use allejo\stakx\Manager\AssetManager;
use allejo\stakx\Markup\RstImageDirective;
use allejo\stakx\Markup\RstSyntaxBlock;
use allejo\stakx\Service;
use Gregwar\RST\Parser;

class RstEngine extends Parser implements MarkupEngineInterface
{
    private readonly RstImageDirective $imageDirective;

    public function __construct(AssetManager $assetManager, $environment = null, $kernel = null)
    {
        parent::__construct($environment, $kernel);

        $this->imageDirective = new RstImageDirective();
        $this->imageDirective->setAssetManager($assetManager);

        $this->registerDirective($this->imageDirective);
        $this->registerDirective(new RstSyntaxBlock());
        $this->setIncludePolicy(true, Service::getWorkingDirectory());
    }

    public function parse($content, $contentItem = null): string
    {
        $this->imageDirective->setContentItem($contentItem);

        return parent::parse($content)->render();
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
