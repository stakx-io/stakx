<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

interface TwigDocumentInterface
{
    /**
     * @param string $filePath
     */
    public function __construct($filePath);

    /**
     * @return string
     */
    public function getNamespace();

    /**
     * @param string $namespace
     *
     * @return void
     */
    public function setNamespace($namespace);

    /**
     * @param PageView $pageView
     *
     * @return void
     */
    public function setPageView(&$pageView);

    /**
     * @return string
     */
    public function getRelativeFilePath();

    /**
     * @return string
     */
    public function getExtension();

    /**
     * @return string
     */
    public function getFilePath();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return bool
     */
    public function isDraft();

    /**
     * @return void
     */
    public function refreshFileContent();
}
