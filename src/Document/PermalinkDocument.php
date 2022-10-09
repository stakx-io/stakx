<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

/**
 * A document that will have a permalink.
 */
interface PermalinkDocument
{
    /**
     * If a document has extra redirects defined in a special manner, overload this function.
     *
     * @return void
     */
    public function handleSpecialRedirects();

    /**
     * Get the destination of where this Content Item would be written to when the website is compiled.
     *
     * @param $permalink string|null Get the target file from a given permalink. When this is null, it will call the
     *                               `permalink()` method.
     *
     * @return string
     */
    public function getTargetFile($permalink = null);

    /**
     * Get the permalink of this Content Item.
     *
     * @return string
     */
    public function getPermalink();

    /**
     * Get an array of URLs that will redirect to.
     *
     * @return string[]
     */
    public function getRedirects();

    /**
     * Get the relative path to the file, with respect to the site root.
     *
     * @return string
     */
    public function getRelativeFilePath();

    /**
     * Build the permalink from whatever information is available.
     *
     * For example, this function can take information from FrontMatter and build the permalink from there.
     *
     * @param bool $force permalinks are often cached internal; set to true to force the permalink to be rebuilt
     *
     * @return void
     */
    public function buildPermalink($force = false);
}
