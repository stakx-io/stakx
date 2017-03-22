<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

class FilesystemExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array();
    }

    public function getFunctions()
    {
        return array(
            FinderFunction::get(),
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'stakx_fs_extension';
    }
}
