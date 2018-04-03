<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use allejo\stakx\Command\BuildableCommand;
use allejo\stakx\Service;
use allejo\stakx\Templating\Twig\Extension\AbstractTwigExtension;
use allejo\stakx\Templating\Twig\Extension\TwigFilterInterface;
use allejo\stakx\Templating\Twig\Extension\TwigFunctionInterface;

class TwigExtension extends \Twig_Extension
{
    private $filters = [];
    private $functions = [];

    public function addFilters(/*iterable*/ $filters)
    {
        /** @var AbstractTwigExtension|TwigFilterInterface $filter */
        foreach ($filters as $filter)
        {
            if (Service::getParameter(BuildableCommand::SAFE_MODE) && $filter::disableInSafeMode())
            {
                continue;
            }

            $this->filters[] = $filter::get();
        }
    }

    public function addFunctions(/*iterable*/ $functions)
    {
        /** @var AbstractTwigExtension|TwigFunctionInterface $fxn */
        foreach ($functions as $fxn)
        {
            if (Service::getParameter(BuildableCommand::SAFE_MODE) && $fxn::disableInSafeMode())
            {
                continue;
            }

            $this->functions[] = $fxn::get();
        }
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getFunctions()
    {
        return $this->functions;
    }

    public function getName()
    {
        return 'stakx_twig_base_extension';
    }
}
