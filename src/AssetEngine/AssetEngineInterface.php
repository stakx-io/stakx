<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\AssetEngine;

use allejo\stakx\Filesystem\WritableFolder;
use allejo\stakx\Manager\PageManager;

/**
 * @since 0.2.0
 */
interface AssetEngineInterface
{
    public const CONTAINER_TAG = 'stakx.asset_engine';

    /**
     * A unique-ish name used to identify this asset engine.
     *
     * This name will be used as an identity throughout stakx's internals and
     * for naming related cache folders.
     */
    public function getName(): string;

    /**
     * The section name in a site's `_config.yml` where this engine can be
     * configured.
     */
    public function getConfigurationNamespace(): string;

    /**
     * The default configuration for this engine.
     *
     * @return array<string, mixed>
     */
    public function getDefaultConfiguration(): array;

    /**
     * The dedicated folder this asset engine will work in.
     *
     * @since 0.2.0
     */
    public function getFolder(): string;

    /**
     * The extension this asset engine will be dedicated to.
     *
     * These extensions will automatically have a `.twig` appended to them.
     *
     * @since 0.2.0
     *
     * @return string[]
     */
    public function getExtensions(): array;

    /**
     * @param array<string, mixed> $options
     *
     * @since 0.2.0
     */
    public function parse(string $content, array $options = []): string;

    /**
     * Set custom options used internally by this AssetEngine.
     *
     * These options are a union of the default configuration and any overrides
     * from the site configuration file.
     *
     * @param array<string, mixed> $options
     *
     * @since 0.2.0
     */
    public function setOptions(array $options): void;

    /**
     * Set the PageManager for this AssetEngine so it can be available if it's
     * available.
     *
     * @since 0.2.0
     */
    public function setPageManager(PageManager $pageManager): void;

    /**
     * Perform loading cache operations before this engine's parsing functionality
     * is called.
     *
     * Any cache-able information should be read from files in the given Folder
     * and unserialize()'d into this engine's properties.
     *
     * This function is only called when stakx is using the `--use-cache` flag.
     *
     * @since 0.2.0
     */
    public function loadCache(WritableFolder $cacheDir): void;

    /**
     * Perform any saving of caches after the parsing functionality has been
     * called.
     *
     * Any cache-able information should be serialize()'d and written to a file
     * in the given Folder.
     *
     * @since 0.2.0
     */
    public function saveCache(WritableFolder $cacheDir): void;
}
