<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use allejo\stakx\Command\BuildableCommand;
use allejo\stakx\MarkupEngine\MarkupEngineManager;
use allejo\stakx\Service;
use allejo\stakx\Templating\Twig\Extension\AbstractTwigExtension;
use allejo\stakx\Templating\Twig\Extension\TwigFilterInterface;
use allejo\stakx\Templating\Twig\Extension\TwigFunctionInterface;
use allejo\stakx\Templating\Twig\MarkupBlock\TokenParser;

class TwigExtension extends \Twig_Extension
{
    private $filters = [];
    private $functions = [];
    private $markupManager;

    public function __construct(MarkupEngineManager $manager)
    {
        $this->markupManager = $manager;
    }

    public function __call($name, $arguments)
    {
        $tag = lcfirst(str_replace('parseMarkup', '', $name));

        if (!$tag)
        {
            throw new \BadMethodCallException("No method $name found in this class.");
        }

        return $this->parseMarkup($arguments[0], $tag);
    }

    public function parseMarkup($content, $tag)
    {
        return $this->markupManager->getEngineByTag($tag)->parse($content);
    }

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
        $filters = $this->filters;

        foreach ($this->markupManager->getTemplateTags() as $tag)
        {
            // Since we can't pass what tag/markup language we're using to the callable, let's make the callable to a
            // non-existent method that will be handled by __call()
            $filters[] = new \Twig_SimpleFilter(
                $tag,
                [$this, 'parseMarkup' . ucfirst($tag)],
                ['is_safe' => ['html']]
            );
        }

        return $filters;
    }

    public function getFunctions()
    {
        return $this->functions;
    }

    public function getTokenParsers()
    {
        $tokenParsers = [];

        foreach ($this->markupManager->getTemplateTags() as $tag)
        {
            $tokenParsers[] = new TokenParser($tag);
        }

        return $tokenParsers;
    }

    public function getName()
    {
        return 'stakx_twig_base_extension';
    }
}
