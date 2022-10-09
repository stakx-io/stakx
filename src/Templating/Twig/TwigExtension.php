<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use allejo\stakx\MarkupEngine\MarkupEngineManager;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use allejo\stakx\Templating\Twig\Extension\AbstractTwigExtension;
use allejo\stakx\Templating\Twig\Extension\TwigFilterInterface;
use allejo\stakx\Templating\Twig\Extension\TwigFunctionInterface;
use allejo\stakx\Templating\Twig\MarkupBlock\TokenParser;
use BadMethodCallException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigExtension extends AbstractExtension
{
    private array $filters = [];

    private array $functions = [];

    public function __construct(private readonly MarkupEngineManager $markupManager)
    {
    }

    public function __call($name, $arguments)
    {
        $tag = lcfirst(str_replace('parseMarkup', '', (string)$name));

        if (!$tag) {
            throw new BadMethodCallException("No method {$name} found in this class.");
        }

        return $this->parseMarkup($arguments[0], $tag);
    }

    public function parseMarkup($content, $tag)
    {
        return $this->markupManager->getEngineByTag($tag)->parse($content);
    }

    public function addFilters(/* iterable */ $filters): void
    {
        /** @var AbstractTwigExtension|TwigFilterInterface $filter */
        foreach ($filters as $filter) {
            if (Service::hasRunTimeFlag(RuntimeStatus::IN_SAFE_MODE) && $filter::disableInSafeMode()) {
                continue;
            }

            $this->filters[] = $filter::get();
        }
    }

    public function addFunctions(/* iterable */ $functions): void
    {
        /** @var AbstractTwigExtension|TwigFunctionInterface $fxn */
        foreach ($functions as $fxn) {
            if (Service::hasRunTimeFlag(RuntimeStatus::IN_SAFE_MODE) && $fxn::disableInSafeMode()) {
                continue;
            }

            $this->functions[] = $fxn::get();
        }
    }

    public function getFilters()
    {
        $filters = $this->filters;

        foreach ($this->markupManager->getTemplateTags() as $tag) {
            // Since we can't pass what tag/markup language we're using to the callable, let's make the callable to a
            // non-existent method that will be handled by __call()
            $filters[] = new TwigFilter(
                $tag,
                [$this, 'parseMarkup' . ucfirst((string)$tag)],
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

        foreach ($this->markupManager->getTemplateTags() as $tag) {
            $tokenParsers[] = new TokenParser($tag);
        }

        return $tokenParsers;
    }

    public function getName()
    {
        return 'stakx_twig_base_extension';
    }
}
