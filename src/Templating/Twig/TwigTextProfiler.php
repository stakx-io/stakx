<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use Twig\Profiler\Dumper\TextDumper;
use Twig\Profiler\Profile;

class TwigTextProfiler extends TextDumper
{
    private $templateMappings;

    /**
     * @param string[] $templateMappings
     */
    public function setTemplateMappings($templateMappings)
    {
        $this->templateMappings = $templateMappings;
    }

    protected function formatTemplate(Profile $profile, $prefix)
    {
        return sprintf('%s└ %s', $prefix, $this->getMappedTemplateName($profile));
    }

    protected function formatNonTemplate(Profile $profile, $prefix)
    {
        return sprintf('%s└ %s::%s(%s)', $prefix, $this->getMappedTemplateName($profile), $profile->getType(), $profile->getName());
    }

    private function getMappedTemplateName(Profile $profile)
    {
        $name = $profile->getTemplate();

        return isset($this->templateMappings[$name]) ? $this->templateMappings[$name] : $profile->getTemplate();
    }
}
