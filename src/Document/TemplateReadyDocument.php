<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use ArrayAccess;

/**
 * An interface for any object that matches the following criteria.
 *
 * - A document which may be represented by a unique permalink; not an object that stores the pattern of a permalink for
 *   its children.
 */
interface TemplateReadyDocument extends ArrayAccess, \IteratorAggregate, \JsonSerializable
{
    /**
     * Create a JailedDocument instance from the object implementing this interface.
     */
    public function createJail(): JailedDocument;

    /**
     * Get the permalink for a document.
     */
    public function getPermalink(): string;

    /**
     * Get any redirects pointing to this document.
     *
     * @return string[]
     */
    public function getRedirects(): array;

    /**
     * Get the file path that this document will be written to.
     *
     * @param mixed $permalink string|null Get the target file from a given permalink. When this is null, it will call the
     *                         `permalink()` method.
     */
    public function getTargetFile($permalink = null): string;

    /**
     * Get the parsed content of this document's body.
     *
     * @return array|string
     */
    public function getContent();

    /**
     * Get the filename of the original document.
     */
    public function getFilename(): string;

    /**
     * Get the filename of the original document without the extension.
     */
    public function getBasename(): string;

    /**
     * Get the file extension of the original document.
     */
    public function getExtension(): string;

    /**
     * Get whether or not this document is a draft.
     */
    public function isDraft(): bool;
}
