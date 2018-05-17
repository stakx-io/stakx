<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

class PlainTextEngine implements MarkupEngine
{
    /**
     * {@inheritdoc}
     */
    public function getTemplateTag()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            'txt',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function parse($content)
    {
        return $content;
    }
}
