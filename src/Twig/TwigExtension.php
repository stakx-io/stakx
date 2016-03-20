<?php

namespace allejo\stakx\Twig;

class TwigExtension extends \Twig_Extension
{
    public function getFilters ()
    {
        return array(
            WhereFilter::get()
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName ()
    {
        return 'stakx_extension';
    }
}