<?php

namespace allejo\stakx\Filesystem;

/**
 * This object allows you scan a folder and get a list of files that have been modified meeting given criteria.
 *
 * @since 0.2.0
 */
class FilesystemDiff
{
    /** @var FileExplorerDefinition[] */
    private $folderDefs;

    /**
     * @param FileExplorerDefinition[] $folderDefs Folders this class will be watching
     */
    public function __construct(array $folderDefs)
    {
        $this->folderDefs = $folderDefs;
    }

    /**
     * Get a list of Files that have been modified after a specific timestamp.
     *
     * @param \DateTime $lastTime
     *
     * @throws \Exception
     *
     * @return File[]
     */
    public function modifiedAfter(\DateTime $lastTime)
    {
        $result = [];

        foreach ($this->folderDefs as $folderDef)
        {
            $explorer = FileExplorer::createFromDefinition($folderDef);
            $explorer->addMatcher(FileExplorerMatcher::modifiedAfter($lastTime));

            $files = $explorer->getFileIterator();

            /** @var File $file */
            foreach ($files as $file)
            {
                $result[] = $file;
            }
        }

        return $result;
    }
}
