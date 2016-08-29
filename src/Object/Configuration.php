<?php

namespace allejo\stakx\Object;

use allejo\stakx\Core\ConsoleInterface;
use allejo\stakx\System\Filesystem;
use allejo\stakx\Utilities\ArrayUtilities;
use Symfony\Component\Console\Output\OutputInterface;
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
     * @var ConsoleInterface
     */
    private $output;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * Configuration constructor.
     *
     * @param string                $configFile
     * @param ConsoleInterface|null $output
     */
    public function __construct($configFile, $output = null)
    {
        $this->configuration = array();
        $this->output        = new ConsoleInterface($output);
        $this->fs            = new Filesystem();

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

    /**
     * @param  string          $name
     * @param  int|string|null $default
     *
     * @return int|string|null
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
                '/^_.*/',
                'node_modules/',
                'stakx-theme.yml'
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