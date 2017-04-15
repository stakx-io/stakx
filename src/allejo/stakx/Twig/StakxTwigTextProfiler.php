<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

use Twig_Profiler_Dumper_Text;
use Twig_Profiler_Profile;

class StakxTwigTextProfiler extends Twig_Profiler_Dumper_Text
{
    private $templateMappings;

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