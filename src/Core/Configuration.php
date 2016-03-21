<?php

namespace allejo\stakx\Core;

use allejo\stakx\Environment\Filesystem;
use allejo\stakx\Utilities\ArrayUtilities;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Configuration
{
    const DEFAULT_NAME = "_config.yml";

    /**
     * An array representation of the main Yaml configuration
     *
     * @var array
     */
    private $configuration;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Configuration constructor.
     *
     * @param string          $configFile
     * @param LoggerInterface $logger
     */
    public function __construct($configFile = Configuration::DEFAULT_NAME, LoggerInterface $logger)
    {
        $this->configuration = array();
        $this->filesystem = new Filesystem();
        $this->logger = $logger;

        if ($this->filesystem->exists($configFile))
        {
            try
            {
                $this->configuration = Yaml::parse(file_get_contents($configFile));
            }
            catch (ParseException $e)
            {
                $this->logger->error("Parsing the configuration failed: {message}", array(
                    "message" => $e->getMessage()
                ));
                $this->logger->error("Using default configuration...");
            }
        }

        $this->defaultConfiguration();
    }

    public function isDebug ()
    {
        return $this->configuration['debug'];
    }

    public function getTheme ()
    {
        return $this->configuration['theme'];
    }

    public function getConfiguration ()
    {
        return$this->configuration;
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
            "target" => "_site",
            "theme"  => "bootstrap",
            "debug"  => false,
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