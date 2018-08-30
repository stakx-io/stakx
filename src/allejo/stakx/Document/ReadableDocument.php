<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Filesystem\File;
use allejo\stakx\Utilities\NullableArray;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

abstract class ReadableDocument
{
    /**
     * Do not read the file immediately at construction. The object will need to execute the self::refreshFileContent()
     * manually at some point.
     *
     * @var bool
     */
    protected $noReadOnConstructor = false;

    /** @var string The content of the document's body. */
    protected $bodyContent = '';

    protected $metadata;
    protected $file;

    private $compiled = false;

    /**
     * ReadableDocument Constructor.
     *
     * @throws FileNotFoundException when the file given in the constructor does not exist or may not be accessible
     */
    public function __construct(File $file)
    {
        $this->metadata = new NullableArray();
        $this->file = $file;

        if (!$this->noReadOnConstructor)
        {
            $this->readContent();
        }
    }

    /**
     * Get the contents of this document.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->bodyContent;
    }

    /**
     * Manually set the contents of this document.
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->bodyContent = $content;
    }

    /**
     * @return string
     */
    public function getIndexName()
    {
        return $this->getRelativeFilePath();
    }

    /**
     * When a document is compiled, all of its internals are finished being configured.
     */
    final public function compile()
    {
        if ($this->compiled)
        {
            return;
        }

        $this->beforeCompile();

        $this->compiled = true;
    }

    /**
     * Determine whether or not this document has been compiled.
     *
     * @return bool
     */
    final protected function isCompiled()
    {
        return $this->compiled;
    }

    /**
     * Get the original File object given to this document.
     *
     * @return File
     */
    final public function getFile()
    {
        return $this->file;
    }

    /**
     * Get the relative path to the file, with respect to the site root.
     *
     * @return string
     */
    final public function getRelativeFilePath()
    {
        return $this->file->getRelativeFilePath();
    }

    /**
     * Get the extension of the file.
     *
     * @return string
     */
    final public function getExtension()
    {
        return $this->file->getExtension();
    }

    /**
     * Get the name of the file without the extension.
     *
     * @return string
     */
    final public function getBasename()
    {
        return $this->file->getBasename();
    }

    /**
     * Get the absolute path to the file.
     *
     * @return string
     */
    final public function getAbsoluteFilePath()
    {
        return $this->file->getAbsolutePath();
    }

    /**
     * Get the name of the file with its extension.
     *
     * @return string
     */
    final public function getFilename()
    {
        return $this->file->getFilename();
    }

    /**
     * Gets the last modified time.
     *
     * @return int The last modified time for the file, in a Unix timestamp
     */
    final public function getLastModified()
    {
        return $this->file->getLastModified();
    }

    /**
     * Read the contents of this file and handle all of the necessary processing/setup for this document.
     */
    final public function readContent()
    {
        $beforeEvent = $this->beforeReadContents();
        $actualEvent = $this->readContents($beforeEvent);
        $this->afterReadContents($actualEvent);
    }

    /**
     * Prepare the Document so it can handle the data that's about to be read in.
     *
     * @return mixed any information that will be passed to the readContents() method
     */
    protected function beforeReadContents()
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
     * @throws \RuntimeException when the file cannot be read
     *
     * @return mixed
     */
    abstract protected function readContents($mixed);

    /**
     * After the Document's content has been read, process the it and handle any parsing that's needed.
     *
     * @param mixed $mixed any information returned from the readContents() method
     */
    protected function afterReadContents($mixed)
    {
    }

    /**
     * Functionality that needs to take place before this document is considered "compiled," meaning everything has been
     * processed, configured, and built.
     */
    protected function beforeCompile()
    {
    }
}
