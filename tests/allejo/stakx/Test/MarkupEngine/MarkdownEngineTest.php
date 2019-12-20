<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Engines\Markdown;

use allejo\stakx\MarkupEngine\MarkdownEngine;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class MarkdownEngineTest extends PHPUnit_Stakx_TestCase
{
    /** @var MarkdownEngine */
    private $mdEngine;

    public function setUp()
    {
        parent::setUp();

        $this->mdEngine = new MarkdownEngine();
    }

    public function testHeaderIdAttr()
    {
        $content = '# Hello World';
        $expected = '<h1 id="hello-world">Hello World</h1>';
        $compiled = $this->mdEngine->parse($content);

        $this->assertEquals($expected, $compiled);
    }

    public function testCodeBlockWithLanguage()
    {
        $codeBlock = <<<'CODE'
```php
<?php

echo "hello world";
```
CODE;
        $compiled = $this->mdEngine->parse($codeBlock);

        $this->assertContains('<code class="hljs language-php">', $compiled);
    }

    public function testCodeBlockWithLanguageSingleLineNumber()
    {
        $codeBlock = <<<'CODE'
```php{3}
<?php

echo "hello world";
```
CODE;
        $compiled = $this->mdEngine->parse($codeBlock);
        $chunks = explode("\n", $compiled);

        $this->assertContains('<code class="hljs language-php">', $chunks[0]);
        $this->assertNotContains('<div class="loc highlighted">', $chunks[1]);
        $this->assertContains('<div class="loc highlighted">', $chunks[2]);
    }

    public function testCodeBlockWithLanguageSingleLineRange()
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
        $chunks = explode("\n", $compiled);

        $this->assertContains('<code class="hljs language-php">', $chunks[0]);
        $this->assertNotContains('<div class="loc highlighted">', $chunks[4]);
        $this->assertContains('<div class="loc highlighted">', $chunks[5]);
        $this->assertContains('<div class="loc highlighted">', $chunks[6]);
        $this->assertContains('<div class="loc highlighted">', $chunks[7]);
    }

    public function testCodeBlockWithLanguageTwoSingleNumbers()
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
        $chunks = explode("\n", $compiled);

        $this->assertContains('<code class="hljs language-php">', $chunks[0]);
        $this->assertNotContains('<div class="loc highlighted">', $chunks[0]);
        $this->assertNotContains('<div class="loc highlighted">', $chunks[2]);
        $this->assertContains('<div class="loc highlighted">', $chunks[3]);
        $this->assertContains('<div class="loc highlighted">', $chunks[6]);
    }

    public function testCodeBlockWithLanguageSingleNumberAndRange()
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
        $chunks = explode("\n", $compiled);

        $this->assertContains('<code class="hljs language-php">', $chunks[0]);
        $this->assertContains('<div class="loc highlighted">', $chunks[0]);
        $this->assertNotContains('<div class="loc highlighted">', $chunks[2]);
        $this->assertContains('<div class="loc highlighted">', $chunks[5]);
        $this->assertContains('<div class="loc highlighted">', $chunks[6]);
        $this->assertContains('<div class="loc highlighted">', $chunks[7]);
    }

    public function testCodeBlockWithNoLanguage()
    {
        $codeBlock = <<<CODE
```
Plain text!
```
CODE;
        $compiled = $this->mdEngine->parse($codeBlock);

        $this->assertContains('<code>', $compiled);
        $this->assertNotContains('language-', $compiled);
    }

    public function testCodeBlockWithUnsupportedLanguage()
    {
        $this->setExpectedException(\PHPUnit_Framework_Error_Warning::class);

        $codeBlock = <<<CODE
```toast
toast("some made up");
```
CODE;
        $this->mdEngine->parse($codeBlock);
    }
}
