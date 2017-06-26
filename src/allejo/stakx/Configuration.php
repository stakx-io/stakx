<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

use allejo\stakx\Exception\FileAccessDeniedException;
use allejo\stakx\Exception\RecursiveConfigurationException;
use allejo\stakx\System\Filesystem;
use allejo\stakx\Utilities\ArrayUtilities;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Configuration implements LoggerAwareInterface
{
    const HIGHLIGHTER_ENABLED = 'highlighter-enabled';

    const DEFAULT_NAME = '_config.yml';
    const IMPORT_KEYWORD = 'import';
    const CACHE_FOLDER = '.stakx-cache';

    private static $configImports = array();

    /**
     * A list of regular expressions or files directly related to stakx websites that should not be copied over to the
     * compiled website as an asset.
     *
     * @var array
     */
    public static $stakxSourceFiles = array('/^_(?!themes).*/', '/.twig$/');

    /**
     * An array representation of the main Yaml configuration.
     *
     * @var array
     */
    private $configuration;

    /**
     * @var string
     */
    private $parentConfig;

    /** @var string */
    private $currentFile;

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
        $this->fs = new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->output = $logger;
    }

    ///
    // Getters
    ///

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->returnConfigOption('debug', false);
    }

    /**
     * @TODO 1.0.0 Remove support for 'base' in next major release; it has been replaced by 'baseurl'
     *
     * @return mixed|null
     */
    public function getBaseUrl()
    {
        $base = $this->returnConfigOption('base');
        $baseUrl = $this->returnConfigOption('baseurl');

        if (is_null($base) || (!empty($baseUrl)))
        {
            return $baseUrl;
        }

        return $base;
    }

    /**
     * @return string[]
     */
    public function getDataFolders()
    {
        return $this->returnConfigOption('data');
    }

    /**
     * @return string[]
     */
    public function getDataSets()
    {
        return $this->returnConfigOption('datasets');
    }

    /**
     * @return string[]
     */
    public function getIncludes()
    {
        return $this->returnConfigOption('include', array());
    }

    /**
     * @return string[]
     */
    public function getExcludes()
    {
        return $this->returnConfigOption('exclude', array());
    }

    /**
     * @return array
     */
    public function getHighlighterCustomLanguages()
    {
        return $this->configuration['highlighter']['languages'];
    }

    /**
     * @return bool
     */
    public function isHighlighterEnabled()
    {
        return $this->configuration['highlighter']['enabled'];
    }

    /**
     * @return string
     */
    public function getTheme()
    {
        return $this->returnConfigOption('theme');
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return string[]
     */
    public function getPageViewFolders()
    {
        return $this->returnConfigOption('pageviews');
    }

    /**
     * @return string
     */
    public function getTargetFolder()
    {
        return $this->returnConfigOption('target');
    }

    /**
     * @return string[][]
     */
    public function getCollectionsFolders()
    {
        return $this->returnConfigOption('collections');
    }

    /**
     * @return bool
     */
    public function getTwigAutoescape()
    {
        return $this->configuration['twig']['autoescape'];
    }

    /**
     * @return false|string
     */
    public function getRedirectTemplate()
    {
        return $this->configuration['templates']['redirect'];
    }

    /**
     * Return the specified configuration option if available, otherwise return the default.
     *
     * @param string     $name    The configuration option to lookup
     * @param mixed|null $default The default value returned if the configuration option isn't found
     *
     * @return mixed|null
     */
    private function returnConfigOption($name, $default = null)
    {
        return isset($this->configuration[$name]) ? $this->configuration[$name] : $default;
    }

    ///
    // Parsing
    ///

    /**
     * Safely read a YAML configuration file and return an array representation of it.
     *
     * This function will only read files from within the website folder.
     *
     * @param  string $filePath
     *
     * @return array
     */
    private static function readFile($filePath)
    {
        $fs = new Filesystem();
        $fileRaw = $fs->safeReadFile($filePath);
        $parsed = Yaml::parse($fileRaw);

        return (null === $parsed) ? array() : $parsed;
    }

    /**
     * Parse a configuration file.
     *
     * @param string|null $configFile
     */
    public function parse($configFile = null)
    {
        $this->parentConfig = $this->fs->getRelativePath($configFile);
        self::$configImports = array();

        $this->configuration = $this->parseConfig($configFile);
        $this->mergeDefaultConfiguration();
        $this->handleDeprecations();

        self::$configImports = array();
    }

    /**
     * Parse a given configuration file and return an associative array representation.
     *
     * This function will automatically take care of imports in each file, whether it be a child or grandchild config
     * file. `$configFile` should be called with 'null' when "configuration-less" mode is used.
     *
     * @param string|null $configFile     The path to the configuration file. If null, the default configuration will be
     *                                    used
     *
     * @return array
     */
    private function parseConfig($configFile = null)
    {
        if (null === $configFile)
        {
            return array();
        }

        $this->currentFile = $configFile;

        try
        {
            $this->isRecursiveImport($configFile);

            $parsedConfig = self::readFile($configFile);

            $this->handleImports($parsedConfig);

            unset($parsedConfig[self::IMPORT_KEYWORD]);
            return $parsedConfig;
        }
        catch (ParseException $e)
        {
            $this->output->error('{file}: parsing failed... {message}', array(
                'message' => $e->getMessage(),
                'file' => $configFile,
            ));
            $this->output->error('Using default configuration...');
        }
        catch (RecursiveConfigurationException $e)
        {
            $this->output->error("{file}: you can't recursively import a file that's already been imported: {import}", array(
                'file' => $configFile,
                'import' => $e->getRecursiveImport(),
            ));
        }

        return array();
    }

    /**
     * Merge the default configuration with the parsed configuration.
     */
    private function mergeDefaultConfiguration()
    {
        $defaultConfig = array(
            'baseurl'   => '',
            'target'    => '_site',
            'twig'      => array(
                'autoescape' => false,
            ),
            'include'   => array(
                '.htaccess',
            ),
            'exclude'   => array(
                'node_modules/',
                'stakx-theme.yml',
                '/tmp___$/',
                self::DEFAULT_NAME,
            ),
            'templates' => array(
                'redirect' => false,
            ),
            'highlighter' => array(
                'enabled' => true,
                'languages' => array(),
            ),
            'build' => array(
                'preserveCase' => false
            ),
        );

        $this->configuration = ArrayUtilities::array_merge_defaults($defaultConfig, $this->configuration, 'name');
    }

    /**
     * Warn about deprecated keywords in the configuration file.
     */
    private function handleDeprecations()
    {
        // @TODO 1.0.0 handle 'base' deprecation in _config.yml
        $base = $this->returnConfigOption('base');

        if (!is_null($base))
        {
            $this->output->warning("The 'base' configuration option has been replaced by 'baseurl' and will be removed in in version 1.0.0.");
        }
    }

    /**
     * Recursively resolve imports for a given array.
     *
     * This modifies the array in place.
     *
     * @param array $configuration
     */
    private function handleImports(array &$configuration)
    {
        if (!array_key_exists(self::IMPORT_KEYWORD, $configuration))
        {
            $this->output->debug('{file}: does not import any other files', array(
                'file' => $this->parentConfig,
            ));

            return;
        }

        if (!is_array($imports = $configuration[self::IMPORT_KEYWORD]))
        {
            $this->output->error('{file}: the reserved "import" keyword can only be an array');

            return;
        }

        $parentConfigLocation = $this->fs->getFolderPath($this->parentConfig);

        foreach ($imports as $import)
        {
            $this->handleImport($import, $parentConfigLocation, $configuration);
        }
    }

    /**
     * Resolve a single import definition.
     *
     * @param string $importDef     The path for a given import; this will be treated as a relative path to the parent
     *                              configuration
     * @param string $parentConfLoc The path to the parent configuration
     * @param array  $configuration The array representation of the current configuration; this will be modified in place
     */
    private function handleImport($importDef, $parentConfLoc, array &$configuration)
    {
        if (!is_string($importDef))
        {
            $this->output->error('{file}: invalid import: {message}', array(
                'file' => $this->parentConfig,
                'message' => $importDef,
            ));

            return;
        }

        $import = $this->fs->appendPath($parentConfLoc, $importDef);

        if (!$this->isValidImport($import))
        {
            return;
        }

        $this->output->debug('{file}: imports additional file: {import}', array(
            'file' => $this->parentConfig,
            'import' => $import,
        ));

        try
        {
            $importedConfig = $this->parseConfig($import);
            $configuration = $this->mergeImports($importedConfig, $configuration);
        }
        catch (FileAccessDeniedException $e)
        {
            $this->output->warning('{file}: trying access file outside of project directory: {import}', array(
                'file' => $this->parentConfig,
                'import' => $import,
            ));
        }
        catch (FileNotFoundException $e)
        {
            $this->output->warning('{file}: could not find file to import: {import}', array(
                'file' => $this->parentConfig,
                'import' => $import,
            ));
        }
    }

    /**
     * Check whether a given file path is a valid import.
     *
     * @param  string $filePath
     *
     * @return bool
     */
    private function isValidImport($filePath)
    {
        $errorMsg = '';

        if ($this->fs->isDir($filePath))
        {
            $errorMsg = 'a directory';
        }
        elseif ($this->fs->isSymlink($filePath))
        {
            $errorMsg = 'a symbolically linked file';
        }
        elseif ($this->fs->absolutePath($this->currentFile) == $this->fs->absolutePath($filePath))
        {
            $errorMsg = 'yourself';
        }
        elseif (($ext = $this->fs->getExtension($filePath)) != 'yml' && $ext != 'yaml')
        {
            $errorMsg = 'a non-YAML configuration';
        }

        if (!($noErrors = empty($errorMsg)))
        {
            $this->output->error("{file}: you can't import {message}: {import}", array(
                'file' => $this->parentConfig,
                'message' => $errorMsg,
                'import' => $filePath
            ));
        }

        return $noErrors;
    }

    /**
     * Check whether or not a filename has already been imported in a given process.
     *
     * @param string $filePath
     */
    private function isRecursiveImport($filePath)
    {
        if (in_array($filePath, self::$configImports))
        {
            throw new RecursiveConfigurationException($filePath, sprintf(
                'The %s file has already been imported', $filePath
            ));
        }

        self::$configImports[] = $filePath;
    }

    /**
     * Merge the given array with existing configuration.
     *
     * @param  array $importedConfig
     * @param  array $existingConfig
     *
     * @return array
     */
    private function mergeImports(array $importedConfig, array $existingConfig)
    {
        $arraySplit = ArrayUtilities::associative_array_split(self::IMPORT_KEYWORD, $existingConfig, false);
        $beforeImport = ArrayUtilities::array_merge_defaults($arraySplit[0], $importedConfig, 'name');
        $result = ArrayUtilities::array_merge_defaults($beforeImport, $arraySplit[1], 'name');

        return $result;
    }
}
