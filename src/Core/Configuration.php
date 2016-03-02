<?php

namespace allejo\stakx\Core;

use Symfony\Component\Yaml\Yaml;

class Configuration
{
    private $configuration;

    public function __construct($configFile)
    {
        $this->configuration = Yaml::parse(file_get_contents($configFile));

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