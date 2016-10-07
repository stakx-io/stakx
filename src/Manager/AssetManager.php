<?php

namespace allejo\stakx\Manager;

use Symfony\Component\Finder\SplFileInfo;

class AssetManager extends TrackingManager
{
    /**
     * The location of where to write files to
     *
     * @var Folder
     */
    private $outputDirectory;

    /**
     * Files or patterns to exclude from copying
     *
     * @var array
     */
    private $excludes;

    /**
     * Files or patterns to ensure are copied regardless of excluded patterns
     *
     * @var array
     */
    private $includes;

    /**
     * AssetManager constructor.
     *
     * @param array $includes
     * @param array $excludes
     */
    public function __construct($includes = array(), $excludes = array())
    {
        parent::__construct();

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