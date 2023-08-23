<?php

/**
 * @copyright 2023 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use __;
use Twig\TwigFunction;
use Twig_Environment;

class AFunction extends AbstractTwigExtension implements TwigFunctionInterface
{
    public function __invoke(Twig_Environment $env, $href, $text, $attributes = [])
    {
        $format = '<a href="%s"%s>%s</a>';

        $config = $env->getGlobals()['site'];
        $internalHosts = __::get($config, 'links.internalhosts', []);
        $externalRel = __::get($config, 'links.externalrel', false);
        $externalTarget = __::get($config, 'links.externaltarget', false);

        if ($externalRel || $externalTarget)
        {
            // Parse out the host from the URL, if any
            $host = parse_url($href, PHP_URL_HOST);

            // If a host was found, and it is not on the list of internal hosts, add the attributes
            if ($host !== NULL && !in_array($host, $internalHosts))
            {
                if ($externalRel && !isset($attributes['rel']))
                {
                    $attributes['rel'] = $externalRel;
                }

                if ($externalTarget && !isset($attributes['target']))
                {
                    $attributes['target'] = $externalTarget;
                }
            }
        }

        $attrStr = '';
        if (sizeof($attributes) > 0)
        {
            $attrStr = ' ' . implode(' ', array_filter(array_map(
                function ($k, $v)
                {
                    if (strlen($v) === 0)
                    {
                        return '';
                    }

                    return $k .= '="' . htmlspecialchars($v) . '"';
                },
                array_keys($attributes),
                $attributes
            )));
        }

        return sprintf($format, $href, $attrStr, $text);
    }

    public static function get()
    {
        return new TwigFunction('a', new self(), [
            'needs_environment' => true,
        ]);
    }
}
