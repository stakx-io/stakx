<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Document\DataItem;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\ReadableDocument;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Document\StaticPageView;

class FileMapper
{
    const UNKNOWN = -1;

    const CONTENT_ITEM = 0;
    const DATA_ITEM = 1;

    const BASE_PAGEVIEW = 2;
    const STATIC_PAGEVIEW = 3;
    const DYNAMIC_PAGEVIEW = 4;
    const REPEATER_PAGEVIEW = 5;

    const TWIG_INCLUDE = 6;

    private $templateIncludes = [];
    private $templateExtends = [];
    private $metadataMap = [];
    private $folderMap = [];
    private $fileMap = [];

    public function __construct()
    {
    }

    public function registerFile(ReadableDocument $file)
    {
        $relativePath = $file->getRelativeFilePath();

        $this->fileMap[$relativePath] = $this->getObjectType($file);
    }

    public function registerFolder($folder, $folderType)
    {
        $this->folderMap[$folder] = $folderType;
    }

    public function registerMetadata($key, $value)
    {
        $this->metadataMap[$key] = $value;
    }

    public function registerTemplateInclude($include, $file)
    {
        $this->templateIncludes[$include][] = $file;
    }

    public function registerTemplateExtend($extend, $file)
    {
        $this->templateExtends[$extend][] = $file;
    }

    private function getObjectType(ReadableDocument $file)
    {
        $objectType = get_class($file);

        switch ($objectType)
        {
            case ContentItem::class:
                return self::CONTENT_ITEM;

            case DataItem::class:
                return self::DATA_ITEM;

            case StaticPageView::class:
                return self::STATIC_PAGEVIEW;

            case DynamicPageView::class:
                return self::DYNAMIC_PAGEVIEW;

            case RepeaterPageView::class:
                return self::REPEATER_PAGEVIEW;
        }

        if ($file->getExtension() === 'twig')
        {
            return self::TWIG_INCLUDE;
        }

        return self::UNKNOWN;
    }
}
