<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Filesystem\Folder;

class AssetManager extends TrackingManager
{
    /**
     * The location of where to write files to.
     *
     * @var Folder
     */
    protected $outputDirectory;

    /**
     * Files or patterns to exclude from copying.
     *
     * @var array
     */
    protected $excludes;

    /**
     * Files or patterns to ensure are copied regardless of excluded patterns.
     *
     * @var array
     */
    protected $includes;

    public function configureFinder($includes = array(), $excludes = array())
    {
        $this->excludes = $excludes;
        $this->includes = $includes;
    }

    /**
     * Set the target directory of where files should be written to.
     *
     * @param Folder $directory
     */
    public function setFolder($directory)
    {
        $this->outputDirectory = $directory;
    }

    /**
     * Copy all of the assets.
     */
    public function copyFiles()
    {
        $this->scanTrackableItems(
            getcwd(),
            array(
                'prefix' => '',
            ),
            $this->includes,
            array_merge(
                array('_themes'),
                $this->excludes
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function refreshItem($filePath)
    {
        return $this->handleTrackableItem($filePath, array(
            'prefix' => ''
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function shouldBeTracked($filePath)
    {
        return $this->fileExplorer->matchesPattern($filePath);
    }

    /**
     * {@inheritdoc}
     */
    public function createNewItem($filePath)
    {
        return $this->handleTrackableItem($filePath, array(
            'prefix' => '',
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTrackableItem($file, array $options = array())
    {
        if (is_string($file))
        {
            $file = ltrim($this->fs->appendPath($options['prefix'], $file), DIRECTORY_SEPARATOR);
            $file = $this->fs->createFileObject($file);
        }

        if (!$this->fs->exists($file))
        {
            return;
        }

        $filePath = $file->getRealPath();
        $pathToStrip = $this->fs->appendPath(getcwd(), $options['prefix']);
        $siteTargetPath = ltrim(str_replace($pathToStrip, '', $filePath), DIRECTORY_SEPARATOR);

        try
        {
            $this->addFileToTracker($file);
            $this->saveTrackerOptions($file->getRelativeFilePath(), $options);

            $this->outputDirectory->copyFile($filePath, $siteTargetPath);
            $this->output->info('Copying file: {file}...', array(
                'file' => $file->getRelativeFilePath(),
            ));
        }
        catch (\Exception $e)
        {
            $this->output->error($e->getMessage());
        }
    }
}
