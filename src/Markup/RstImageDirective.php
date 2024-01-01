<?php

namespace allejo\stakx\Markup;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Manager\AssetManager;
use Doctrine\RST\Directives\Directive;
use Doctrine\RST\Nodes\Node;
use Doctrine\RST\Parser;

/**
 * This custom ImageDirective is used to hook into the RST parser and listen in for linked assets to register them with
 * our AssetManager.
 *
 * @since 0.2.1
 */
class RstImageDirective extends Directive
{
    use AssetHandlerTrait;

    public function getName(): string
    {
        return 'image';
    }

    public function setAssetManager(AssetManager $assetManager): void
    {
        $this->assetManager = $assetManager;
    }

    public function setContentItem(?ContentItem $contentItem): void
    {
        $this->contentItem = $contentItem;
    }

    public function processNode(
        Parser $parser,
        string $variable,
        string $data,
        array $options
    ): ?Node {
        $environment = $parser->getEnvironment();

        $url = $environment->relativeUrl($data);

        if ($url === null) {
            throw new \RuntimeException(sprintf('Could not get relative url for %s', $data));
        }

        $this->registerAsset($url);

        return $parser->getNodeFactory()->createImageNode($url, $options);
    }
}
