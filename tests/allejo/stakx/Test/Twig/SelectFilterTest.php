<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\test\Twig;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use allejo\stakx\Twig\SelectFilter;

class SelectFilterTest extends PHPUnit_Stakx_TestCase
{
    private function getDummyCollection()
    {
        return $this->createMultipleVirtualFiles(ContentItem::class, array(
            array('frontmatter' => array('tags' => array('red', 'blue'))),
            array('frontmatter' => array('tags' => array('red', 'green'))),
            array('frontmatter' => array('tags' => array('green', 'blue', 'orange'))),
        ));
    }

    public function testSelectFilterWithDefaultSettings()
    {
        $posts = $this->getDummyCollection();

        $filter = new SelectFilter();
        $results = $filter($posts, 'tags');

        $this->assertCount(4, $results);

        foreach (array('red', 'blue', 'green', 'orange') as $value)
        {
            $this->assertContains($value, $results);
        }
    }

    public function testSelectFilterWithFlattenOnly()
    {
        $posts = $this->getDummyCollection();
        $filter = new SelectFilter();
        $results = $filter($posts, 'tags', false);

        $this->assertCount(3, $results);
        $this->assertContains(array('red', 'blue'), $results);
    }

    public function testSelectFilterWithFlattenNoDistinct()
    {
        $posts = $this->getDummyCollection();
        $filter = new SelectFilter();
        $results = $filter($posts, 'tags', true, false);

        $this->assertCount(7, $results);
    }
}
