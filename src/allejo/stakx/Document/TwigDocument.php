<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

interface TwigDocument extends JailableDocument
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
    public function getObjectName();

    /**
     * @return bool
     */
    public function isDraft();

    /**
     * Read the contents of a file.
     *
     * This function is responsible for reading the file and doing whatever is needed to parse & save the contents into
     * the given object.
     *
     * @return void
     */
    public function refreshFileContent();
}
