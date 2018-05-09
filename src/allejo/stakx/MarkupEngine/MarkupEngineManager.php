<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

use __;
use allejo\stakx\Exception\UnsupportedMarkupException;

class MarkupEngineManager
{
    private $engines;

    public function __construct()
    {
        $this->engines = [];
    }

    public function addMarkupEngines(/*iterable*/ $markupEngines)
    {
        foreach ($markupEngines as $markupEngine)
        {
            $this->addMarkupEngine($markupEngine);
        }
    }

    public function addMarkupEngine(MarkupEngine $markupEngine)
    {
        $extensions = $markupEngine->getExtensions();
        $primaryExt = __::first($extensions);

        foreach ($extensions as $k => $extension)
        {
            if ($k === 0)
            {
                $this->engines[$extension] = $markupEngine;
            }
            else
            {
                $this->engines[$extension] = &$this->engines[$primaryExt];
            }
        }
    }

    public function getMarkupEngine($extension)
    {
        if (isset($this->engines[$extension]))
        {
            return $this->engines[$extension];
        }

        throw new UnsupportedMarkupException($extension, 'There is no support to handle this markup format.');
    }
}
