<?php

namespace foonoo\tests\Unit\Content;

use foonoo\content\MarkupContent;
use foonoo\sites\FrontMatterReader;
use foonoo\tests\BaseTestCase;
use foonoo\text\TextConverter;
use Symfony\Component\Yaml\Parser;

class MarkupContentTest extends BaseTestCase
{
    public function testRenderAndMetaData(): void
    {
        $doc = $this->createVfsFile(
            'site/about.md',
            "---\ntitle: About Us\nauthor: Foonoo Team\n---\n# About Us\nWelcome to our page."
        );

        $textConverter = $this->createMock(TextConverter::class);
        $textConverter->expects($this->once())
            ->method('convert')
            ->with("# About Us\nWelcome to our page.", 'md', 'html')
            ->willReturn('<h1>About Us</h1><p>Welcome to our page.</p>');

        $frontMatterReader = new FrontMatterReader(new Parser());
        $markupContent = new MarkupContent($textConverter, $frontMatterReader, $doc, 'about.html');

        $this->assertSame('about.html', $markupContent->getDestination());
        $this->assertSame(['frontmatter' => ['title' => 'About Us', 'author' => 'Foonoo Team']], $markupContent->getMetaData());
        $this->assertSame('<h1>About Us</h1><p>Welcome to our page.</p>', $markupContent->render());
        $this->assertNotEmpty($markupContent->getID());
    }
}
