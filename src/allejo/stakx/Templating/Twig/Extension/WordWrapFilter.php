<?php

namespace allejo\stakx\Templating\Twig\Extension;

/**
 * This filter is adapted from the Twig Text extension.
 *
 * @copyright 2009 Fabien Potencier
 * @author Henrik Bjornskov <hb@peytz.dk>
 * @see https://github.com/twigphp/Twig-extensions
 */
class WordWrapFilter extends AbstractTwigExtension implements TwigFilterInterface
{
    public function __invoke(\Twig_Environment $env, $value, $length = 80, $separator = "\n", $preserve = false)
    {
        $sentences = array();

        $previous = mb_regex_encoding();
        mb_regex_encoding($env->getCharset());

        $pieces = mb_split($separator, $value);
        mb_regex_encoding($previous);

        foreach ($pieces as $piece)
        {
            while (!$preserve && mb_strlen($piece, $env->getCharset()) > $length)
            {
                $sentences[] = mb_substr($piece, 0, $length, $env->getCharset());
                $piece = mb_substr($piece, $length, 2048, $env->getCharset());
            }

            $sentences[] = $piece;
        }

        return implode($separator, $sentences);
    }

    public static function get()
    {
        return new \Twig_SimpleFilter('wordwrap', new self(), [
            'needs_environment' => true,
        ]);
    }
}
