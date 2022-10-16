<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

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

    /**
     * @param string[] $options
     */
    public function processNode(Parser $parser, string $variable, string $data, array $options): ?Node
    {
        $environment = $parser->getEnvironment();
        $url = $environment->relativeUrl($data);

        $this->registerAsset($url);

        return $parser->getNodeFactory()->createImageNode($url, $options);
    }
}
