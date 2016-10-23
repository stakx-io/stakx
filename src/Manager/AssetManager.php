<?php

namespace allejo\stakx\Manager;

use allejo\stakx\System\Folder;
use Symfony\Component\Finder\SplFileInfo;

class AssetManager extends TrackingManager
{
    /**
     * The location of where to write files to
     *
     * @var Folder
     */
    protected $outputDirectory;

    /**
     * Files or patterns to exclude from copying
     *
     * @var array
     */
    protected $excludes;

    /**
     * Files or patterns to ensure are copied regardless of excluded patterns
     *
     * @var array
     */
    protected $includes;

    public function configureFinder ($includes = array(), $excludes = array())
    {
        $this->excludes = $excludes;
        $this->includes = $includes;
    }

    /**
     * Set the target directory of where files should be written to
     *
     * @param Folder $directory
     */
    public function setFolder ($directory)
    {
        $this->outputDirectory = $directory;
    }

    /**
     * Copy all of the assets
     */
    public function copyFiles()
    {
        $this->scanTrackableItems(
            '.',
            array(
                'prefix' => ''
            ),
            $this->includes,
            $this->excludes
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTrackableItem($file, $options = array())
    {
        if (is_string($file))
        {
            $file = $this->fs->appendPath($options['prefix'], $file);
        }

        if (!$this->fs->exists($file)) { return; }

        $filePath = $file->getRealPath();
        $pathToStrip = $this->fs->appendPath(getcwd(), $options['prefix']);
        $siteTargetPath = ltrim(str_replace($pathToStrip, "", $filePath), DIRECTORY_SEPARATOR);

        try
        {
            $this->addArrayToTracker(
                $file->getRelativePathname(),
                array(),
                $file->getRelativePathname()
            );
            $this->saveTrackerOptions($file->getRelativePathname(), $options);
            $this->outputDirectory->copyFile($filePath, $siteTargetPath);
            $this->output->info('Copying file: {file}...', array(
                'file' => $file->getRelativePathname()
            ));
        }
        catch (\Exception $e)
        {
            $this->output->error($e->getMessage());
        }
    }
}