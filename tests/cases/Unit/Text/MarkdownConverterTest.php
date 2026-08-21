<?php

namespace foonoo\tests\Unit\Text;

use foonoo\tests\BaseTestCase;
use foonoo\text\MarkdownConverter;
use Parsedown;

class MarkdownConverterTest extends BaseTestCase
{
    private MarkdownConverter $converter;

    public function setUp(): void
    {
        parent::setUp();
        $this->converter = new MarkdownConverter(new Parsedown());
    }

    public function testHeadingConversion(): void
    {
        $html = $this->converter->convert("# Hello World\n## Subheading");
        $this->assertStringContainsString('<h1>Hello World</h1>', $html);
        $this->assertStringContainsString('<h2>Subheading</h2>', $html);
    }

    public function testListAndParagraphConversion(): void
    {
        $markdown = "This is a paragraph.\n\n- Item 1\n- Item 2";
        $html = $this->converter->convert($markdown);
        $this->assertStringContainsString('<p>This is a paragraph.</p>', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Item 1</li>', $html);
        $this->assertStringContainsString('<li>Item 2</li>', $html);
    }

    public function testCodeBlockConversion(): void
    {
        $markdown = "```php\necho 'hello';\n```";
        $html = $this->converter->convert($markdown);
        $this->assertStringContainsString('<pre><code class="language-php">', $html);
        $this->assertStringContainsString("echo 'hello';", $html);
    }

    public function testLinksAndEmphasis(): void
    {
        $markdown = "[Foonoo](https://foonoo.org) is **awesome** and *fast*.";
        $html = $this->converter->convert($markdown);
        $this->assertStringContainsString('<a href="https://foonoo.org">Foonoo</a>', $html);
        $this->assertStringContainsString('<strong>awesome</strong>', $html);
        $this->assertStringContainsString('<em>fast</em>', $html);
    }
}
