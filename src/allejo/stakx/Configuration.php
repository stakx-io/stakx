<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

use __;
use allejo\stakx\Exception\FileAccessDeniedException;
use allejo\stakx\Exception\RecursiveConfigurationException;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Utilities\ArrayUtilities;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Configuration implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
     * The master configuration file for the current build.
     *
     * This is the file that will be handling imports, if any.
     *
     * @var File
     */
    private $configFile;

    /**
     * The current configuration file being processed.
     *
     * If there are no imports used, this value will equal $this->configFile. Otherwise, this file will equal to the
     * current imported configuration file that is being evaluated.
     *
     * @var File
     */
    private $currentFile;

    /**
     * Configuration constructor.
     */
    public function __construct()
    {
        $this->configuration = array();
    }

    ///
    // Getters
    ///

    /**
     * @return bool
     */
    public function isDebug()
    {
        return __::get($this->configuration, 'debug', false);
    }

    /**
     * @return string|null
     */
    public function getBaseUrl()
    {
        return __::get($this->configuration, 'baseurl');
    }

    public function hasDataItems()
    {
        return ($this->getDataFolders() !== null || $this->getDataSets() !== null);
    }

    public function hasCollections()
    {
        return ($this->getCollectionsFolders() !== null);
    }

    /**
     * @return string[]
     */
    public function getDataFolders()
    {
        return __::get($this->configuration, 'data');
    }

    /**
     * @return string[]
     */
    public function getDataSets()
    {
        return __::get($this->configuration, 'datasets');
    }

    /**
     * @return string[]
     */
    public function getIncludes()
    {
        return __::get($this->configuration, 'include', []);
    }

    /**
     * @return string[]
     */
    public function getExcludes()
    {
        return __::get($this->configuration, 'exclude', []);
    }

    /**
     * @return array
     */
    public function getHighlighterCustomLanguages()
    {
        return __::get($this->configuration, 'highlighter.languages', []);
    }

    /**
     * @return bool
     */
    public function isHighlighterEnabled()
    {
        return __::get($this->configuration, 'highlighter.enabled', true);
    }

    /**
     * @return string
     */
    public function getTheme()
    {
        return __::get($this->configuration, 'theme');
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
        return __::get($this->configuration, 'pageviews', []);
    }

    /**
     * @return string
     */
    public function getTargetFolder()
    {
        return __::get($this->configuration, 'target');
    }

    /**
     * @return string[][]
     */
    public function getCollectionsFolders()
    {
        return __::get($this->configuration, 'collections', []);
    }

    /**
     * @return bool
     */
    public function getTwigAutoescape()
    {
        return __::get($this->configuration, 'twig.autoescape');
    }

    /**
     * @return false|string
     */
    public function getRedirectTemplate()
    {
        return __::get($this->configuration, 'templates.redirect');
    }

    ///
    // Parsing
    ///

    /**
     * Parse a configuration file.
     *
     * @param File|null $configFile
     */
    public function parse(File $configFile = null)
    {
        $this->configFile = $configFile;
        self::$configImports = [];

        $this->configuration = $this->parseConfig($configFile);
        $this->mergeDefaultConfiguration();
        $this->handleDefaultOperations();
        $this->handleDeprecations();

        self::$configImports = [];
    }

    /**
     * Parse a given configuration file and return an associative array representation.
     *
     * This function will automatically take care of imports in each file, whether it be a child or grandchild config
     * file. `$configFile` should be called with 'null' when "configuration-less" mode is used.
     *
     * @param File|null $configFile The path to the configuration file. If null, the default configuration will be
     *                              used
     *
     * @return array
     */
    private function parseConfig(File $configFile = null)
    {
        if ($configFile === null)
        {
            return [];
        }

        $this->currentFile = $configFile;

        try
        {
            $this->isRecursiveImport($configFile);

            $parsedConfig = Yaml::parse($configFile->getContents());

            if ($parsedConfig === null)
            {
                $parsedConfig = [];
            }

            $this->handleImports($parsedConfig);

            unset($parsedConfig[self::IMPORT_KEYWORD]);

            return $parsedConfig;
        }
        catch (ParseException $e)
        {
            $this->logger->error('{file}: parsing failed... {message}', array(
                'message' => $e->getMessage(),
                'file' => $configFile,
            ));
            $this->logger->error('Using default configuration...');
        }
        catch (RecursiveConfigurationException $e)
        {
            $this->logger->error("{file}: you can't recursively import a file that's already been imported: {import}", array(
                'file' => $configFile,
                'import' => $e->getRecursiveImport(),
            ));
        }

        return [];
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
        // Nothing deprecated right now
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
        if (!isset($configuration[self::IMPORT_KEYWORD]))
        {
            $this->logger->debug('{file}: does not import any other files', array(
                'file' => $this->currentFile->getRelativeFilePath(),
            ));

            return;
        }

        if (!is_array($imports = $configuration[self::IMPORT_KEYWORD]))
        {
            $this->logger->error('{file}: the reserved "import" keyword can only be an array');

            return;
        }

        foreach ($imports as $import)
        {
            $this->handleImport($import, $configuration);
        }
    }

    /**
     * Resolve a single import definition.
     *
     * @param string $importDef     The path for a given import; this will be treated as a relative path to the parent
     *                              configuration
     * @param array  $configuration The array representation of the current configuration; this will be modified in place
     */
    private function handleImport($importDef, array &$configuration)
    {
        if (!is_string($importDef))
        {
            $this->logger->error('{file}: invalid import: {message}', array(
                'file' => $this->configFile->getRelativeFilePath(),
                'message' => $importDef,
            ));

            return;
        }

        $import = $this->configFile->createFileForRelativePath($importDef);

        if (!$this->isValidImport($import))
        {
            return;
        }

        $this->logger->debug('{file}: imports additional file: {import}', array(
            'file' => $this->configFile->getRelativeFilePath(),
            'import' => $import->getRelativeFilePath(),
        ));

        try
        {
            $importedConfig = $this->parseConfig($import);
            $configuration = $this->mergeImports($importedConfig, $configuration);
        }
        catch (FileAccessDeniedException $e)
        {
            $this->logger->warning('{file}: trying access file outside of project directory: {import}', array(
                'file' => $this->configFile->getRelativeFilePath(),
                'import' => $import,
            ));
        }
        catch (FileNotFoundException $e)
        {
            $this->logger->warning('{file}: could not find file to import: {import}', array(
                'file' => $this->configFile->getRelativeFilePath(),
                'import' => $import,
            ));
        }
    }

    /**
     * Check whether a given file path is a valid import.
     *
     * @param  File $filePath
     *
     * @return bool
     */
    private function isValidImport(File $filePath)
    {
        $errorMsg = '';

        if ($filePath->isDir())
        {
            $errorMsg = 'a directory';
        }
        elseif ($filePath->isLink())
        {
            $errorMsg = 'a symbolically linked file';
        }
        elseif ($this->currentFile->getAbsolutePath() == $filePath->getAbsolutePath())
        {
            $errorMsg = 'yourself';
        }
        elseif (($ext = $filePath->getExtension()) != 'yml' && $ext != 'yaml')
        {
            $errorMsg = 'a non-YAML configuration';
        }

        if (!($noErrors = empty($errorMsg)))
        {
            $this->logger->error("{file}: you can't import {message}: {import}", array(
                'file' => $this->configFile->getRelativeFilePath(),
                'message' => $errorMsg,
                'import' => $filePath
            ));
        }

        return $noErrors;
    }

    /**
     * Check whether or not a filename has already been imported in a given process.
     *
     * @param File $filePath
     */
    private function isRecursiveImport(File $filePath)
    {
        if (in_array($filePath->getRelativeFilePath(), self::$configImports))
        {
            throw new RecursiveConfigurationException($filePath, sprintf(
                'The %s file has already been imported', $filePath->getRelativeFilePath()
            ));
        }

        self::$configImports[] = $filePath->getRelativeFilePath();
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

    private function handleDefaultOperations()
    {
        if (substr($this->getTargetFolder(), 0, 1) != '_')
        {
            $this->configuration['exclude'][] = $this->getTargetFolder();
        }

        Service::setParameter('build.preserveCase', $this->configuration['build']['preserveCase']);
    }
}
