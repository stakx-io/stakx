<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Document;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Test\StakxTestCase;
use BadMethodCallException;
use DateTime;
use DateTimeZone;

/**
 * @internal
 *
 * @coversNothing
 */
class JailedDocumentTest extends StakxTestCase
{
    public function getJailObject()
    {
        $url = fs::appendPath(__DIR__, '..', 'assets', 'PageViews', 'jail.html.twig');
        $pageView = new StaticPageView($this->createFileObjectFromPath($url));
        $pageView->evaluateFrontMatter();

        return $pageView->createJail();
    }

    public function testJailObjectGetPermalink(): void
    {
        $permalink = '/authors/scott-pilgrim/';
        $jail = $this->getJailObject();

        $this->assertEquals($permalink, $jail->getPermalink());
        $this->assertEquals($jail->getPermalink(), $jail['permalink']);
    }

    public function testJailObjectGenericFrontMatter(): void
    {
        $jail = $this->getJailObject();

        $this->assertTrue($jail['active']);
        $this->assertCount(4, $jail['powers']);
        $this->assertInstanceOf(DateTime::class, $jail['joined']);
        $this->assertNull($jail['my-fake-key']);
    }

    public function testJailObjectGetRedirects(): void
    {
        $jail = $this->getJailObject();

        $this->assertNotNull($jail['redirects']);
        $this->assertEmpty($jail['redirects']);
        $this->assertEquals($jail->getRedirects(), $jail['redirects']);
    }

    public function testJailGetDateTimeTimezone(): void
    {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        $jail = $this->getJailObject();

        $this->assertEquals(new DateTimeZone('America/New_York'), $jail['joined']->getTimezone());

        date_default_timezone_set($defaultTimezone);
    }

    public function testJailWhiteListFunction(): void
    {
        $contentItem = $this->createFrontMatterDocumentOfType(ContentItem::class);
        $jailable = $contentItem->createJail();
        $content = $jailable->getContent();

        $this->assertNotEmpty($content);
    }

    public function testJailFrontMatter(): void
    {
        $value = 'super bacon!';

        $contentItem = $this->createFrontMatterDocumentOfType(ContentItem::class, null, ['value' => $value]);
        $contentItem->evaluateFrontMatter();

        $jailable = $contentItem->createJail();

        $this->assertEquals($value, $jailable['value']);
    }

    public function testJailInvalidFunction(): void
    {
        $this->expectException(BadMethodCallException::class);

        /** @var ContentItem $contentItem */
        $contentItem = $this->createFrontMatterDocumentOfType(ContentItem::class);
        $jailable = $contentItem->createJail();
        $jailable->getLineOffset();
    }
}
