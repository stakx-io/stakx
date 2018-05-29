<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\AssetEngine;

/**
 * @since 0.2.0
 */
interface AssetEngine
{
    const CONTAINER_TAG = 'stakx.asset_engine';

    /**
     * The section name in a site's `_config.yml` where this engine can be configured.
     *
     * @return string
     */
    public function getConfigurationNamespace();

    /**
     * The default configuration for this engine.
     *
     * @return array
     */
    public function getDefaultConfiguration();

    /**
     * The dedicated folder this asset engine will work in.
     *
     * @since 0.2.0
     *
     * @return string
     */
    public function getFolder();

    /**
     * The extension this asset engine will be dedicated to.
     *
     * These extensions will automatically have a `.twig` appended to them.
     *
     * @since 0.2.0
     *
     * @return string[]
     */
    public function getExtensions();

    /**
     * @param string $content
     *
     * @since 0.2.0
     *
     * @return string
     */
    public function parse($content);
}
