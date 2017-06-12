<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

class TwigExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            GroupByFilter::get(),
            OrderFilter::get(),
            WhereFilter::get(),
            SelectFilter::get(),
            ZipFilter::get(),
            Md5Filter::get(),
            Sha1Filter::get(),
        );
    }

    public function getFunctions()
    {
        return array(
            BaseUrlFunction::get(),
            FileFunction::get(),
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'stakx_core_extension';
    }
}
