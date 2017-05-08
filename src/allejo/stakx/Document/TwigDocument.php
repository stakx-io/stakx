<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\System\FileInterface;

/**
 * This interface defines the requirements for an object that will be available through Twig.
 */
interface TwigDocument extends
    \IteratorAggregate,
    FileInterface,
    JailableDocument
{
    /**
     * @param string $filePath
     */
    public function __construct($filePath);

    /**
     * @return string
     */
    public function getObjectName();

    /**
     * @return bool
     */
    public function isDraft();
}
