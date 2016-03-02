<?php

namespace allejo\stakx\Core;

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

    public function getTemplateFolders ()
    {
        return $this->configuration["templates"];
    }

    public function getContentFolders ()
    {
        return $this->configuration["content"];
    }

    public function getTargetFolder ()
    {
        return $this->configuration["target"];
    }

    public function getDataFolders ()
    {
        return $this->configuration["data"];
    }

    private function defaultConfiguration()
    {
        $defaultConfig = array(
            "target"  => "_site",
            "collections" => array(
                array(
                    "name" => "posts",
                    "folder" => "_books"
                )
            ),
            "templates" => array(
                "_pages"
            )
        );

        $this->configuration = array_merge_recursive($this->configuration, $defaultConfig);
    }
}