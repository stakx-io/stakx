<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

use __;
use allejo\stakx\Exception\UnsupportedMarkupException;

class MarkupEngineManager
{
    /** @var array<string, MarkupEngineInterface> */
    private array $enginesByTags = [];

    /** @var array<string, MarkupEngineInterface> */
    private array $enginesByExtension = [];

    /**
     * @param iterable<MarkupEngineInterface> $markupEngines
     */
    public function addMarkupEngines(iterable $markupEngines): void
    {
        foreach ($markupEngines as $markupEngine) {
            $this->addMarkupEngine($markupEngine);
        }
    }

    public function addMarkupEngine(MarkupEngineInterface $markupEngine): void
    {
        $extensions = $markupEngine->getExtensions();
        $primaryExt = __::first($extensions);

        foreach ($extensions as $k => $extension) {
            if ($k === 0) {
                $this->enginesByExtension[$extension] = $markupEngine;
            } else {
                $this->enginesByExtension[$extension] = &$this->enginesByExtension[$primaryExt];
            }
        }

        $this->enginesByTags[$markupEngine->getTemplateTag()] = &$this->enginesByExtension[$primaryExt];
    }

    public function getEngineByTag($tag): MarkupEngineInterface
    {
        if (isset($this->enginesByTags[$tag])) {
            return $this->enginesByTags[$tag];
        }

        throw new UnsupportedMarkupException($tag, 'There is no support to handle this markup format.');
    }

    public function getEngineByExtension($extension): MarkupEngineInterface
    {
        if (isset($this->enginesByExtension[$extension])) {
            return $this->enginesByExtension[$extension];
        }

        throw new UnsupportedMarkupException($extension, 'There is no support to handle this markup format.');
    }

    public function getTemplateTags(): array
    {
        return array_filter(array_keys($this->enginesByTags));
    }

    public function getSupportedExtensions(): array
    {
        return array_keys($this->enginesByExtension);
    }
}
