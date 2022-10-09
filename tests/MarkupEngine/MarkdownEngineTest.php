<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\MarkupEngine;

use allejo\stakx\MarkupEngine\MarkdownEngine;
use allejo\stakx\Test\StakxTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class MarkdownEngineTest extends StakxTestCase
{
    private MarkdownEngine $mdEngine;

    public function setUp(): void
    {
        parent::setUp();

        $this->mdEngine = new MarkdownEngine($this->getMockAssetManager());
    }

    public function testHeaderIdAttr(): void
    {
        $content = '# Hello World';
        $expected = '<h1 id="hello-world">Hello World</h1>';
        $compiled = $this->mdEngine->parse($content);

        $this->assertEquals($expected, $compiled);
    }

    public function testCodeBlockWithLanguage(): void
    {
        $codeBlock = <<<'CODE'
```php
<?php

echo "hello world";
```
CODE;
        $compiled = $this->mdEngine->parse($codeBlock);

        $this->assertStringContains('<code class="hljs language-php">', $compiled);
    }

    public function testCodeBlockWithLanguageSingleLineNumber(): void
    {
        $codeBlock = <<<'CODE'
```php{3}
<?php

echo "hello world";
```
CODE;
        $compiled = $this->mdEngine->parse($codeBlock);
        $chunks = explode("\n", (string)$compiled);

        $this->assertStringContains('<code class="hljs language-php">', $chunks[0]);
        $this->assertStringNotContainsString('<div class="loc highlighted">', $chunks[1]);
        $this->assertStringContains('<div class="loc highlighted">', $chunks[2]);
    }

    public function testCodeBlockWithLanguageSingleLineRange(): void
    {
        $codeBlock = <<<'CODE'
```php{6-8}
<?php

/**
 * Hello World
 *
 * @api
 * @since 1.0.0
 * @param string $str Some string parameter
 */
```
CODE;
        $compiled = $this->mdEngine->parse($codeBlock);
        $chunks = explode("\n", (string)$compiled);

        $this->assertStringContains('<code class="hljs language-php">', $chunks[0]);
        $this->assertStringNotContainsString('<div class="loc highlighted">', $chunks[4]);
        $this->assertStringContains('<div class="loc highlighted">', $chunks[5]);
        $this->assertStringContains('<div class="loc highlighted">', $chunks[6]);
        $this->assertStringContains('<div class="loc highlighted">', $chunks[7]);
    }

    public function testCodeBlockWithLanguageTwoSingleNumbers(): void
    {
        $codeBlock = <<<'CODE'
```php{4,7}
<?php

/**
 * Hello World
 *
 * @api
 * @since 1.0.0
 * @param string $str Some string parameter
 */
```
CODE;
        $compiled = $this->mdEngine->parse($codeBlock);
        $chunks = explode("\n", (string)$compiled);

        $this->assertStringContains('<code class="hljs language-php">', $chunks[0]);
        $this->assertStringNotContainsString('<div class="loc highlighted">', $chunks[0]);
        $this->assertStringNotContainsString('<div class="loc highlighted">', $chunks[2]);
        $this->assertStringContains('<div class="loc highlighted">', $chunks[3]);
        $this->assertStringContains('<div class="loc highlighted">', $chunks[6]);
    }

    public function testCodeBlockWithLanguageSingleNumberAndRange(): void
    {
        $codeBlock = <<<'CODE'
```php{6-8,1}
<?php

/**
 * Hello World
 *
 * @api
 * @since 1.0.0
 * @param string $str Some string parameter
 */
```
CODE;
        $compiled = $this->mdEngine->parse($codeBlock);
        $chunks = explode("\n", (string)$compiled);

        $this->assertStringContains('<code class="hljs language-php">', $chunks[0]);
        $this->assertStringContains('<div class="loc highlighted">', $chunks[0]);
        $this->assertStringNotContainsString('<div class="loc highlighted">', $chunks[2]);
        $this->assertStringContains('<div class="loc highlighted">', $chunks[5]);
        $this->assertStringContains('<div class="loc highlighted">', $chunks[6]);
        $this->assertStringContains('<div class="loc highlighted">', $chunks[7]);
    }

    public function testCodeBlockWithNoLanguage(): void
    {
        $codeBlock = <<<'CODE'
```
Plain text!
```
CODE;
        $compiled = $this->mdEngine->parse($codeBlock);

        $this->assertStringContains('<code>', $compiled);
        $this->assertStringNotContainsString('language-', $compiled);
    }

    public function testCodeBlockWithUnsupportedLanguage(): void
    {
        $this->expectWarning();

        $codeBlock = <<<'CODE'
```toast
toast("some made up");
```
CODE;
        $this->mdEngine->parse($codeBlock);
    }
}
