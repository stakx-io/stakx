<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use Twig\Profiler\Dumper\BaseDumper;
use Twig\Profiler\Profile;

class TwigTextProfiler extends BaseDumper
{
    /** @var string[] */
    private array $templateMappings;

    /**
     * @param string[] $templateMappings
     */
    public function setTemplateMappings(array $templateMappings): void
    {
        $this->templateMappings = $templateMappings;
    }

    protected function formatTemplate(Profile $profile, $prefix): string
    {
        return sprintf('%s└ %s', $prefix, $this->getMappedTemplateName($profile));
    }

    protected function formatNonTemplate(Profile $profile, $prefix): string
    {
        return sprintf('%s└ %s::%s(%s)', $prefix, $this->getMappedTemplateName($profile), $profile->getType(), $profile->getName());
    }

    protected function formatTime(Profile $profile, $percent): string
    {
        return sprintf('%.2fms/%.0f%%', $profile->getDuration() * 1000, $percent);
    }

    private function getMappedTemplateName(Profile $profile)
    {
        $name = $profile->getTemplate();

        return $this->templateMappings[$name] ?? $profile->getTemplate();
    }
}
