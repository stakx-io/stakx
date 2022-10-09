<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Filesystem\File;
use allejo\stakx\Utilities\NullableArray;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

abstract class ReadableDocument
{
    /**
     * Do not read the file immediately at construction. The object will need to execute the self::refreshFileContent()
     * manually at some point.
     */
    protected bool $noReadOnConstructor = false;

    /** @var string The content of the document's body. */
    protected string $bodyContent = '';

    protected $metadata;

    private bool $compiled = false;

    /**
     * ReadableDocument Constructor.
     *
     * @throws FileNotFoundException when the file given in the constructor does not exist or may not be accessible
     */
    public function __construct(protected File $file)
    {
        $this->metadata = new NullableArray();

        if (!$this->noReadOnConstructor) {
            $this->readContent();
        }
    }

    /**
     * Get the contents of this document.
     */
    public function getContent(): mixed
    {
        return $this->bodyContent;
    }

    /**
     * Manually set the contents of this document.
     *
     * @param string $content
     */
    public function setContent($content): void
    {
        $this->bodyContent = $content;
    }

    public function getIndexName(): string
    {
        return $this->getRelativeFilePath();
    }

    /**
     * When a document is compiled, all of its internals are finished being configured.
     */
    final public function compile(): void
    {
        if ($this->compiled) {
            return;
        }

        $this->beforeCompile();

        $this->compiled = true;
    }

    /**
     * Get the value stored under this key in the file's internal metadata only available to stakx.
     *
     * @param string $key
     *
     * @return null|mixed
     */
    final public function getMetadata($key): mixed
    {
        return $this->metadata[$key];
    }

    /**
     * Set a value in the file's internal metadata only available to stakx.
     *
     * @param string $key
     */
    final public function setMetadata($key, mixed $value): mixed
    {
        return $this->metadata[$key] = $value;
    }

    /**
     * Get the original File object given to this document.
     */
    final public function getFile(): File
    {
        return $this->file;
    }

    /**
     * Get the relative path to the file, with respect to the site root.
     */
    final public function getRelativeFilePath(): string
    {
        return $this->file->getRelativeFilePath();
    }

    /**
     * Get the extension of the file.
     */
    final public function getExtension(): string
    {
        return $this->file->getExtension();
    }

    /**
     * Get the name of the file without the extension.
     */
    final public function getBasename(): string
    {
        return $this->file->getBasename();
    }

    /**
     * Get the absolute path to the file.
     */
    final public function getAbsoluteFilePath(): string
    {
        return $this->file->getAbsolutePath();
    }

    /**
     * Get the name of the file with its extension.
     */
    final public function getFilename(): string
    {
        return $this->file->getFilename();
    }

    /**
     * Gets the last modified time.
     *
     * @return int The last modified time for the file, in a Unix timestamp
     */
    final public function getLastModified(): int
    {
        return $this->file->getLastModified();
    }

    /**
     * Read the contents of this file and handle all of the necessary processing/setup for this document.
     */
    final public function readContent(): void
    {
        $beforeEvent = $this->beforeReadContents();
        $actualEvent = $this->readContents($beforeEvent);
        $this->afterReadContents($actualEvent);
    }

    /**
     * Determine whether or not this document has been compiled.
     */
    final protected function isCompiled(): bool
    {
        return $this->compiled;
    }

    /**
     * Prepare the Document so it can handle the data that's about to be read in.
     *
     * @return mixed any information that will be passed to the readContents() method
     */
    protected function beforeReadContents(): mixed
    {
        return null;
    }

    /**
     * Read the contents of the file and store the information internally **only**.
     *
     * This method must **not** handle any parsing or processing of the file's content. That should be handled by the
     * `afterReadContents()` method.
     *
     * @param mixed $mixed any information returned from the beforeReadContents() method
     *
     * @throws RuntimeException when the file cannot be read
     */
    abstract protected function readContents($mixed): mixed;

    /**
     * After the Document's content has been read, process the it and handle any parsing that's needed.
     *
     * @param mixed $mixed any information returned from the readContents() method
     */
    protected function afterReadContents($mixed): void
    {
    }

    /**
     * Functionality that needs to take place before this document is considered "compiled," meaning everything has been
     * processed, configured, and built.
     */
    protected function beforeCompile(): void
    {
    }
}
