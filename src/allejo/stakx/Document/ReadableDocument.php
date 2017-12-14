<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Document\DocumentInterface;
use allejo\stakx\Filesystem\File;
use allejo\stakx\System\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

abstract class ReadableDocument implements DocumentInterface
{
    /**
     * Do not read the file immediately at construction. The object will need to execute the self::refreshFileContent()
     * manually at some point.
     *
     * @var bool
     */
    protected $noReadOnConstructor = false;

    /** @var string */
    protected $filePath;

    /** @var string */
    protected $extension;

    /** @var Filesystem */
    protected $fs;

    /**
     * ReadableDocument Constructor.
     */
    public function __construct(File $filePath)
    {
        $this->fs = new Filesystem();
        $p = $this->filePath = $this->fs->absolutePath((string)$filePath);

        if (!$this->fs->exists($p))
        {
            throw new FileNotFoundException(null, 0, null, $p);
        }

        $this->extension = strtolower($this->fs->getExtension($p));

        if (!$this->noReadOnConstructor)
        {
            $this->refreshFileContent();
        }
    }

    final public function getRelativeFilePath()
    {
        return $this->fs->getRelativePath($this->filePath);
    }

    final public function getExtension()
    {
        return $this->extension;
    }

    final public function getBaseName()
    {
        return $this->fs->getBaseName($this->filePath);
    }

    final public function getAbsoluteFilePath()
    {
        return $this->filePath;
    }

    final public function getFileName()
    {
        return $this->fs->getFileName($this->filePath);
    }

    abstract public function refreshFileContent();
}
