<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

/**
 * An interface for any object that matches the following criteria.
 *
 * - A document which may be represented by a unique permalink; not an object that stores the pattern of a permalink for
 *   its children.
 */
interface TemplateReadyDocument extends \ArrayAccess, \IteratorAggregate, \JsonSerializable
{
    /**
     * Create a JailedDocument instance from the object implementing this interface.
     *
     * @return JailedDocument
     */
    public function createJail();

    /**
     * Get the permalink for a document.
     *
     * @return string
     */
    public function getPermalink();

    /**
     * Get any redirects pointing to this document.
     *
     * @return string[]
     */
    public function getRedirects();

    /**
     * Get the file path that this document will be written to.
     *
     * @return string
     */
    public function getTargetFile();

    /**
     * Get the parsed content of this document's body.
     *
     * @return string|array
     */
    public function getContent();

    /**
     * Get the filename of the original document.
     *
     * @return string
     */
    public function getFilename();

    /**
     * Get the filename of the original document without the extension.
     *
     * @return string
     */
    public function getBasename();

    /**
     * Get the file extension of the original document.
     *
     * @return string
     */
    public function getExtension();

    /**
     * Get whether or not this document is a draft.
     *
     * @return bool
     */
    public function isDraft();
}
