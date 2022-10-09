<?php

namespace allejo\stakx\Filesystem;

/**
 * @since 0.2.0
 */
class FileExplorerDefinition
{
    /** @var Folder */
    public $folder;

    /** @var string[] */
    public $includes = [];

    /** @var string[] */
    public $excludes = [];

    /** @var int */
    public $flags = null;

    /**
     * @param Folder $folder
     */
    public function __construct(Folder $folder)
    {
        $this->folder = $folder;
    }
}
