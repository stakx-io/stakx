<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DocumentDeprecated;

use allejo\stakx\Document\JailableDocument;
use allejo\stakx\DocumentDeprecated\DocumentInterface;

/**
 * This interface defines the requirements for an object that will be available through Twig.
 */
interface TwigDocument extends
    \IteratorAggregate,
    DocumentInterface,
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
