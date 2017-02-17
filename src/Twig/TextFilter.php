<?php

/**
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Henrik Bjornskov <hb@peytz.dk>
 */
class Twig_Extensions_Extension_Text extends Twig_Extension
{
    /**
     * Returns a list of filters.
     *
     * @return array
     */
    public function getFilters ()
    {
        return array(
            new Twig_SimpleFilter('summary',  'twig_summary_filter'),
            new Twig_SimpleFilter('truncate', 'twig_truncate_filter', array('needs_environment' => true)),
            new Twig_SimpleFilter('wordwrap', 'twig_wordwrap_filter', array('needs_environment' => true)),
        );
    }

    /**
     * Name of this extension.
     *
     * @return string
     */
    public function getName ()
    {
        return 'Text';
    }
}

function twig_summary_filter ($value, $paragraphCount = 1)
{
    if (function_exists('simplexml_load_string'))
    {
        $content = simplexml_load_string('<html>' . $value . '</html>');
        $count = min($paragraphCount, $content->count());
        $children = $content->children();

        $summary = "";

        for ($i = 0; $i < $count; $i++)
        {
            $summary .= $children[$i]->asXml();
        }

        return $summary;
    }

    return $value;
}

function twig_truncate_filter (Twig_Environment $env, $value, $length = 30, $preserve = false, $separator = '...')
{
    if (mb_strlen($value, $env->getCharset()) > $length)
    {
        if ($preserve)
        {
            // If breakpoint is on the last word, return the value without separator.
            if (false === ($breakpoint = mb_strpos($value, ' ', $length, $env->getCharset())))
            {
                return $value;
            }

            $length = $breakpoint;
        }

        return rtrim(mb_substr($value, 0, $length, $env->getCharset())).$separator;
    }

    return $value;
}

function twig_wordwrap_filter (Twig_Environment $env, $value, $length = 80, $separator = "\n", $preserve = false)
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
