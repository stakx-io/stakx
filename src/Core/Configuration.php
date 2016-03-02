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

    public function getLayoutsFolder()
    {
        return $this->configuration["layouts"];
    }

    public function getPostsFolder()
    {
        return $this->configuration["posts"];
    }

    public function getTargetFolder()
    {
        return $this->configuration["target"];
    }

    private function defaultConfiguration()
    {
        $defaultConfig = array(
            "directories" => array(
                "layouts" => "_layouts",
                "target"  => "_site",
                "posts"   => "_posts"
            )
        );

        $this->configuration = array_merge_recursive($this->configuration, $defaultConfig);
    }
}