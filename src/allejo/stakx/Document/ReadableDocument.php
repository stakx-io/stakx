<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
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

    /**
     * ReadableDocument Constructor.
     *
     * @throws FileNotFoundException When the file given in the constructor does not exist or may not be accessible.
     */
    public function __construct(File $file)
    {
        $filePath = (string)$file;

        if (!fs::exists($filePath))
        {
            throw new FileNotFoundException(null, 0, null, $filePath);
        }

        $this->metadata = [];
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
     * Read the contents of the file and handle any parsing that needs to be done.
     *
     * For example, if a file needs to parse and evaluate FrontMatter, that will need to be in this function call after
     * reading the file contents.
     *
     * @throws \RuntimeException When the file cannot be read.
     *
     * @return void
     */
    abstract public function readContent();
}
