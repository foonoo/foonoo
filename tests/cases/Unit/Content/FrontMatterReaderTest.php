<?php

namespace foonoo\tests\Unit\Content;

use foonoo\sites\FrontMatterReader;
use foonoo\tests\BaseTestCase;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class FrontMatterReaderTest extends BaseTestCase
{
    private FrontMatterReader $reader;

    public function setUp(): void
    {
        parent::setUp();
        $this->reader = new FrontMatterReader(new Parser());
    }

    public function testReadWithFrontMatter(): void
    {
        $file = $this->createVfsFile('docs/sample.md', "---\ntitle: Sample Doc\ntags:\n  - guide\n  - test\n---\n# Sample Body\nHello World.");
        [$frontMatter, $body] = $this->reader->read($file);

        $this->assertSame(['title' => 'Sample Doc', 'tags' => ['guide', 'test']], $frontMatter);
        $this->assertSame("# Sample Body\nHello World.", $body);
    }

    public function testReadWithoutFrontMatter(): void
    {
        $file = $this->createVfsFile('docs/plain.md', "# Just Plain Markdown\nNo YAML header here.");
        [$frontMatter, $body] = $this->reader->read($file);

        $this->assertSame([], $frontMatter);
        $this->assertSame("# Just Plain Markdown\nNo YAML header here.", $body);
    }

    public function testReadWithMalformedFrontMatterThrowsException(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Could not parse frontmatter");

        $file = $this->createVfsFile('docs/broken.md', "---\ntitle: [Unclosed list\n---\nBody");
        $this->reader->read($file);
    }
}
