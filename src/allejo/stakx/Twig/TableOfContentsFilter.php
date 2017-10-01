<?php

namespace allejo\stakx\Twig;

class TableOfContentsFilter implements StakxTwigFilter
{
    /**
     * @param string      $html  The HTML we'll be parsing
     * @param string|null $id    The ID to assign to the generated TOC
     * @param string|null $class The class to assign to the generated TOC
     * @param int         $hMin  The heading minimum that'll be included
     * @param int         $hMax  The heading maximum that'll be included
     *
     * @link https://git.io/vdnEM Modified from @mattfarina's implementation
     *
     * @return string
     */
    public function __invoke($html, $id = null, $class = null, $hMin = 1, $hMax = 6)
    {
        if (!function_exists('simplexml_load_string'))
        {
            trigger_error('XML support is not available with the current PHP installation.', E_USER_WARNING);
            return '';
        }

        $content = simplexml_load_string('<html>' . $html . '</html>');

        if ($content === false)
        {
            trigger_error('The HTML given to generate this Table of Contents was invalid.', E_USER_WARNING);
            return '';
        }

        $toc = '';
        $curr = $last = 1;

        $headings = $content->xpath('//h1|//h2|//h3|//h4|//h5|//h6');

        foreach ($headings as $heading)
        {
            if (!property_exists($heading->attributes(), 'id'))
            {
                continue;
            }

            sscanf($heading->getName(), 'h%u', $curr);

            if (!($hMin <= $curr && $curr <= $hMax)) {
                continue;
            }

            $headingID = $heading->attributes()->id;

            // If the current level is greater than the last level indent one level
            if ($curr > $last) {
                $toc .= '<ul>';
            }
            // If the current level is less than the last level go up appropriate amount.
            elseif ($curr < $last) {
                $toc .= str_repeat('</li></ul>', $last - $curr) . '</li>';
            }
            // If the current level is equal to the last.
            else {
                $toc .= '</li>';
            }

            $toc .= '<li><a href="#' . $headingID . '">' . (string)$heading . '</a>';
            $last = $curr;
        }

        $toc .= str_repeat('</li></ul>', $last);

        if ($id !== null || $class !== null)
        {
            $attributes = [];

            if ($id !== null)
            {
                $attributes[] = sprintf('id="%s"', $id);
            }

            if ($class !== null)
            {
                $attributes[] = sprintf('class="%s"', $class);
            }

            $toc = substr_replace($toc, sprintf('<ul %s', implode(' ', $attributes)), 0, 3);
        }

        return $toc;
    }

    /**
     * @return \Twig_SimpleFilter
     */
    public static function get()
    {
        return (new \Twig_SimpleFilter('toc', new self()));
    }
}
