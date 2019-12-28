<?php

namespace allejo\stakx\Markup;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Manager\AssetManager;
use allejo\stakx\MarkupEngine\AssetHandlerTrait;
use Gregwar\RST\HTML\Directives\Image;
use Gregwar\RST\HTML\Nodes\ImageNode;
use Gregwar\RST\Parser;

/**
 * This custom ImageDirective is used to hook into the RST parser and listen in for linked assets to register them with
 * our AssetManager.
 *
 * @since 0.2.1
 */
class RstImageDirective extends Image
{
    use AssetHandlerTrait;

    public function setAssetManager(AssetManager $assetManager)
    {
        $this->assetManager = $assetManager;
    }

    public function setContentItem(ContentItem $contentItem)
    {
        $this->contentItem = $contentItem;
    }

    public function processNode(Parser $parser, $variable, $data, array $options)
    {
        $environment = $parser->getEnvironment();
        $url = $environment->relativeUrl($data);

        $this->registerAsset($url);

        return new ImageNode($url, $options);
    }
}
