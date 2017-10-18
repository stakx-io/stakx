<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Document\DocumentInterface;
use allejo\stakx\Filesystem\File;
use allejo\stakx\System\Filesystem;

abstract class ReadableDocument implements DocumentInterface
{
    /** @var string */
    protected $filePath;
    /** @var string */
    protected $extension;
    /** @var Filesystem */
    protected $fs;

    /**
     * @param string $filePath
     */
    public function __construct(File $filePath)
    {
        $this->fs = new Filesystem();
        $p = $this->filePath = $this->fs->absolutePath((string)$filePath);

        $this->extension = strtolower($this->fs->getExtension($p));
        $this->refreshFileContent();
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
