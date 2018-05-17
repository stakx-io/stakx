<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use Twig_Profiler_Dumper_Text;
use Twig_Profiler_Profile;

class TwigTextProfiler extends Twig_Profiler_Dumper_Text
{
    private $templateMappings;

    /**
     * @param string[] $templateMappings
     */
    public function setTemplateMappings($templateMappings)
    {
        $this->templateMappings = $templateMappings;
    }

    protected function formatTemplate(Twig_Profiler_Profile $profile, $prefix)
    {
        return sprintf('%s└ %s', $prefix, $this->getMappedTemplateName($profile));
    }

    protected function formatNonTemplate(Twig_Profiler_Profile $profile, $prefix)
    {
        return sprintf('%s└ %s::%s(%s)', $prefix, $this->getMappedTemplateName($profile), $profile->getType(), $profile->getName());
    }

    private function getMappedTemplateName(Twig_Profiler_Profile $profile)
    {
        $name = $profile->getTemplate();

        return isset($this->templateMappings[$name]) ? $this->templateMappings[$name] : $profile->getTemplate();
    }
}
