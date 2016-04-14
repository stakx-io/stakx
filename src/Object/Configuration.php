<?php

namespace allejo\stakx\Object;

use allejo\stakx\System\Filesystem;
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
        return $this->returnConfigOption('debug', false);
    }

    public function getBaseUrl ()
    {
        return $this->returnConfigOption('base');
    }

    public function getDataFolders ()
    {
        return $this->returnConfigOption('data');
    }

    public function getDataSets ()
    {
        return $this->returnConfigOption('datasets');
    }

    public function getTheme ()
    {
        return $this->returnConfigOption('theme');
    }

    public function getConfiguration ()
    {
        return $this->configuration;
    }

    public function getPageViewFolders ()
    {
        return $this->returnConfigOption('pageviews');
    }

    public function getTargetFolder ()
    {
        return $this->returnConfigOption('target');
    }

    public function getCollectionsFolders ()
    {
        return $this->returnConfigOption('collections');
    }

    public function getTwigAutoescape ()
    {
        return $this->configuration['twig']['autoescape'];
    }

    private function returnConfigOption ($name, $default = null)
    {
        return (isset($this->configuration[$name]) ? $this->configuration[$name] : $default);
    }

    private function defaultConfiguration()
    {
        $defaultConfig = array(
            "target" => "_site",
            "twig" => array(
                "autoescape" => false
            ),
            "pageviews" => array(
                "_pages"
            )
        );

        $this->configuration = ArrayUtilities::array_merge_defaults($defaultConfig, $this->configuration, 'name');
    }
}