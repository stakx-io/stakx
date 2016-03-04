<?php

namespace allejo\stakx\Core;

use allejo\stakx\Utilities\ArrayUtilities;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class Configuration
{
    const DEFAULT_NAME = "_config.yml";

    private $filesystem;
    private $configuration;

    public function __construct($configFile = Configuration::DEFAULT_NAME)
    {
        $this->filesystem = new Filesystem();
        $this->configuration = array();

        if ($this->filesystem->exists($configFile))
        {
            $this->configuration = Yaml::parse(file_get_contents($configFile));
        }

        $this->defaultConfiguration();
    }

    public function getPageViews ()
    {
        return $this->configuration["pageviews"];
    }

    public function getTargetFolder ()
    {
        return $this->configuration["target"];
    }

    public function getCollectionsFolders ()
    {
        return $this->configuration["collections"];
    }

    private function defaultConfiguration()
    {
        $defaultConfig = array(
            "target"  => "_site",
            "theme" => "bootstrap",
            "collections" => array(
                array(
                    "name" => "posts",
                    "folder" => "_posts"
                )
            ),
            "pageviews" => array(
                "_pages"
            )
        );

        $this->configuration = ArrayUtilities::array_merge_defaults($defaultConfig, $this->configuration, 'name');
    }
}