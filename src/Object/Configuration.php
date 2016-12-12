<?php

namespace allejo\stakx\Object;

use allejo\stakx\System\Filesystem;
use allejo\stakx\Utilities\ArrayUtilities;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Configuration implements LoggerAwareInterface
{
    const DEFAULT_NAME = "_config.yml";

    /**
     * A list of regular expressions or files directly related to stakx websites that should not be copied over to the
     * compiled website as an asset.
     *
     * @var array
     */
    public static $stakxSourceFiles = array('/^_(?!themes).*/', '/.twig$/');

    /**
     * An array representation of the main Yaml configuration
     *
     * @var array
     */
    private $configuration;

    /**
     * @var LoggerInterface
     */
    private $output;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * Configuration constructor.
     */
    public function __construct()
    {
        $this->configuration = array();
        $this->fs            = new Filesystem();
    }

    /**
     * Parse a given configuration file and configure this Configuration instance.
     *
     * This function should be called with 'null' passed when "configuration-less" mode is used
     *
     * @param string|null $configFile The path to the configuration file. If null, the default configuration will be
     *                                used
     */
    public function parseConfiguration ($configFile = null)
    {
        if ($this->fs->exists($configFile))
        {
            try
            {
                $this->configuration = Yaml::parse(file_get_contents($configFile));
            }
            catch (ParseException $e)
            {
                $this->output->error("Parsing the configuration failed: {message}", array(
                    "message" => $e->getMessage()
                ));
                $this->output->error("Using default configuration...");
            }
        }

        $this->defaultConfiguration();
        $this->handleDeprecations();
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->output = $logger;
    }

    public function isDebug ()
    {
        return $this->returnConfigOption('debug', false);
    }

    /**
     * @TODO 1.0.0 Remove support for 'base' in next major release; it has been replaced by 'baseurl'
     *
     * @return mixed|null
     */
    public function getBaseUrl ()
    {
        $base = $this->returnConfigOption('base');

        if (is_null($base))
        {
            return $this->returnConfigOption('baseurl');
        }

        return $base;
    }

    /**
     * @return string[]
     */
    public function getDataFolders ()
    {
        return $this->returnConfigOption('data');
    }

    /**
     * @return string[]
     */
    public function getDataSets ()
    {
        return $this->returnConfigOption('datasets');
    }

    public function getIncludes ()
    {
        return $this->returnConfigOption('include', array());
    }

    public function getExcludes ()
    {
        return $this->returnConfigOption('exclude', array());
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

    public function getRedirectTemplate ()
    {
        return $this->configuration['templates']['redirect'];
    }

    /**
     * Return the specified configuration option if available, otherwise return the default
     *
     * @param  string     $name    The configuration option to lookup
     * @param  mixed|null $default The default value returned if the configuration option isn't found
     *
     * @return mixed|null
     */
    private function returnConfigOption ($name, $default = null)
    {
        return (isset($this->configuration[$name]) ? $this->configuration[$name] : $default);
    }

    private function defaultConfiguration ()
    {
        $defaultConfig = array(
            'baseurl' => '',
            'target' => '_site',
            'twig' => array(
                'autoescape' => false
            ),
            'include' => array(
                '.htaccess'
            ),
            'exclude' => array(
                'node_modules/',
                'stakx-theme.yml',
                self::DEFAULT_NAME
            ),
            'templates' => array(
                'redirect' => false
            )
        );

        if (is_array($this->configuration))
        {
            $this->configuration = ArrayUtilities::array_merge_defaults($defaultConfig, $this->configuration, 'name');
        }
        else
        {
            $this->configuration = $defaultConfig;
        }
    }

    private function handleDeprecations ()
    {
        // @TODO 1.0.0 handle 'base' deprecation in _config.yml
        $base = $this->returnConfigOption('base');

        if (!is_null($base))
        {
            $this->output->warning("The 'base' configuration option has been replaced by 'baseurl' and will be removed in in version 1.0.0.");
        }
    }
}