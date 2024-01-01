<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Compilation;

use __;
use allejo\stakx\Event\ConfigurationParseComplete;
use allejo\stakx\Exception\RecursiveConfigurationException;
use allejo\stakx\Filesystem\File;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use allejo\stakx\Utilities\ArrayUtilities;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Configuration
{
    public const DEFAULT_NAME = '_config.yml';
    public const IMPORT_KEYWORD = 'import';
    public const CACHE_FOLDER = '.stakx-cache';

    private static array $configImports = [];

    /**
     * A list of regular expressions or files directly related to stakx websites that should not be copied over to the
     * compiled website as an asset.
     */
    public static array $stakxSourceFiles = ['/^_(?!themes)/', '/.twig$/'];

    /**
     * An array representation of the main Yaml configuration.
     */
    private array $configuration = [];

    /**
     * The master configuration file for the current build.
     *
     * This is the file that will be handling imports, if any.
     */
    private File $configFile;

    /**
     * The current configuration file being processed.
     *
     * If there are no imports used, this value will equal $this->configFile. Otherwise, this file will equal to the
     * current imported configuration file that is being evaluated.
     */
    private File $currentFile;

    /**
     * Configuration constructor.
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger
    ) {
    }

    ///
    // Getters
    ///

    public function isDebug(): bool
    {
        return __::get($this->configuration, 'debug', false);
    }

    public function getBaseUrl(): ?string
    {
        return __::get($this->configuration, 'baseurl');
    }

    public function hasDataItems(): bool
    {
        return $this->getDataFolders() !== null || $this->getDataSets() !== null;
    }

    public function hasCollections(): bool
    {
        return $this->getCollectionsFolders() !== null;
    }

    /**
     * @return string[]
     */
    public function getDataFolders(): array
    {
        return __::get($this->configuration, 'data');
    }

    /**
     * @return string[]
     */
    public function getDataSets(): array
    {
        return __::get($this->configuration, 'datasets');
    }

    /**
     * @return string[]
     */
    public function getIncludes(): array
    {
        return __::get($this->configuration, 'include', []);
    }

    /**
     * @return string[]
     */
    public function getExcludes(): array
    {
        return __::get($this->configuration, 'exclude', []);
    }

    /**
     * @return string[]
     */
    public function getHighlighterCustomLanguages(): array
    {
        return __::get($this->configuration, 'highlighter.languages', []);
    }

    public function isHighlighterEnabled(): bool
    {
        return __::get($this->configuration, 'highlighter.enabled', true);
    }

    public function isHighlighterUsingLineNumbers(): bool
    {
        return __::get($this->configuration, 'highlighter.line_numbers', false);
    }

    public function getTheme(): string
    {
        return __::get($this->configuration, 'theme');
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @return string[]
     */
    public function getPageViewFolders(): array
    {
        return __::get($this->configuration, 'pageviews', []);
    }

    public function getTargetFolder(): string
    {
        $target = __::get($this->configuration, 'target');
        $target = preg_replace('#[/\\\\]+$#', '', $target);

        return $target . '/';
    }

    /**
     * @return string[][]
     */
    public function getCollectionsFolders(): array
    {
        return __::get($this->configuration, 'collections', []);
    }

    public function getTwigAutoescape(): bool
    {
        return __::get($this->configuration, 'twig.autoescape');
    }

    public function getRedirectTemplate(): bool|string
    {
        return __::get($this->configuration, 'templates.redirect');
    }

    ///
    // Parsing
    ///

    /**
     * Parse a configuration file.
     */
    public function parse(?File $configFile = null): void
    {
        $this->configFile = $configFile;
        self::$configImports = [];

        $this->configuration = $this->parseConfig($configFile);
        $this->mergeDefaultConfiguration();
        $this->handleDefaultOperations();
        $this->handleDeprecations();

        self::$configImports = [];

        $event = new ConfigurationParseComplete($this);
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Parse a given configuration file and return an associative array representation.
     *
     * This function will automatically take care of imports in each file, whether it be a child or grandchild config
     * file. `$configFile` should be called with 'null' when "configuration-less" mode is used.
     *
     * @param File|null $configFile The path to the configuration file. If null, the default configuration will be
     *                              used
     */
    private function parseConfig(?File $configFile = null): array
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
            $this->logger->error('{file}: parsing failed... {message}', [
                'message' => $e->getMessage(),
                'file' => $configFile,
            ]);
            $this->logger->error('Using default configuration...');
        }
        catch (RecursiveConfigurationException $e)
        {
            $this->logger->error("{file}: you can't recursively import a file that's already been imported: {import}", [
                'file' => $configFile,
                'import' => $e->getRecursiveImport(),
            ]);
        }

        return [];
    }

    /**
     * Merge the default configuration with the parsed configuration.
     */
    private function mergeDefaultConfiguration(): void
    {
        $defaultConfig = [
            'baseurl' => '',
            'target' => '_site/',
            'twig' => [
                'autoescape' => false,
            ],
            'include' => [
                '.htaccess',
            ],
            'exclude' => [
                'node_modules/',
                'stakx-theme.yml',
                '/tmp___$/',
                self::DEFAULT_NAME,
            ],
            'templates' => [
                'redirect' => false,
            ],
            'highlighter' => [
                'enabled' => true,
                'line_numbers' => false,
                'languages' => [],
            ],
            'build' => [
                'preserveCase' => false,
            ],
        ];

        $this->configuration = ArrayUtilities::array_merge_defaults($defaultConfig, $this->configuration, 'name');
    }

    /**
     * Warn about deprecated keywords in the configuration file.
     */
    private function handleDeprecations(): void
    {
        // Nothing deprecated right now
    }

    /**
     * Recursively resolve imports for a given array.
     *
     * This modifies the array in place.
     */
    private function handleImports(array &$configuration): void
    {
        if (!isset($configuration[self::IMPORT_KEYWORD]))
        {
            $this->logger->debug('{file}: does not import any other files', [
                'file' => $this->currentFile->getRelativeFilePath(),
            ]);

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
    private function handleImport(string $importDef, array &$configuration): void
    {
        if (!is_string($importDef))
        {
            $this->logger->error('{file}: invalid import: {message}', [
                'file' => $this->configFile->getRelativeFilePath(),
                'message' => $importDef,
            ]);

            return;
        }

        $import = $this->configFile->createFileForRelativePath($importDef);

        if (!$this->isValidImport($import))
        {
            return;
        }

        $this->logger->debug('{file}: imports additional file: {import}', [
            'file' => $this->configFile->getRelativeFilePath(),
            'import' => $import->getRelativeFilePath(),
        ]);

        try
        {
            $importedConfig = $this->parseConfig($import);
            $configuration = $this->mergeImports($importedConfig, $configuration);
        }
        catch (FileNotFoundException $e)
        {
            $this->logger->warning('{file}: could not find file to import: {import}', [
                'file' => $this->configFile->getRelativeFilePath(),
                'import' => $import,
            ]);
        }
    }

    /**
     * Check whether a given file path is a valid import.
     */
    private function isValidImport(File $filePath): bool
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
        elseif ($this->currentFile->getAbsolutePath() === $filePath->getAbsolutePath())
        {
            $errorMsg = 'yourself';
        }
        elseif (($ext = $filePath->getExtension()) !== 'yml' && $ext !== 'yaml')
        {
            $errorMsg = 'a non-YAML configuration';
        }

        if (!($noErrors = empty($errorMsg)))
        {
            $this->logger->error("{file}: you can't import {message}: {import}", [
                'file' => $this->configFile->getRelativeFilePath(),
                'message' => $errorMsg,
                'import' => $filePath,
            ]);
        }

        return $noErrors;
    }

    /**
     * Check whether a filename has already been imported in a given process.
     */
    private function isRecursiveImport(File $filePath): void
    {
        if (in_array($filePath->getRelativeFilePath(), self::$configImports, true))
        {
            throw new RecursiveConfigurationException($filePath, sprintf(
                'The %s file has already been imported', $filePath->getRelativeFilePath()
            ));
        }

        self::$configImports[] = $filePath->getRelativeFilePath();
    }

    /**
     * Merge the given array with existing configuration.
     */
    private function mergeImports(array $importedConfig, array $existingConfig): array
    {
        $arraySplit = ArrayUtilities::associative_array_split(self::IMPORT_KEYWORD, $existingConfig, false);
        $beforeImport = ArrayUtilities::array_merge_defaults($arraySplit[0], $importedConfig, 'name');

        return ArrayUtilities::array_merge_defaults($beforeImport, $arraySplit[1], 'name');
    }

    private function handleDefaultOperations(): void
    {
        if (!str_starts_with($this->getTargetFolder(), '_'))
        {
            $this->configuration['exclude'][] = $this->getTargetFolder();
        }

        if ($this->configuration['build']['preserveCase'])
        {
            Service::setRuntimeFlag(RuntimeStatus::COMPILER_PRESERVE_CASE);
        }
    }
}
