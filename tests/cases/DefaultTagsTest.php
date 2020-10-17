<?php


namespace cases;


use foonoo\events\EventDispatcher;
use foonoo\text\DefaultTags;
use foonoo\text\TagParser;
use foonoo\text\TemplateEngine;
use foonoo\text\TocGenerator;
use ntentan\honam\EngineRegistry;
use ntentan\honam\engines\php\HelperVariable;
use ntentan\honam\engines\php\Janitor;
use ntentan\honam\factories\PhpEngineFactory;
use ntentan\honam\TemplateFileResolver;
use ntentan\honam\TemplateRenderer;
use PHPUnit\Framework\TestCase;

class DefaultTagsTest extends TestCase
{
    private $tagParser;

    public function setUp(): void
    {
        $templateFileResolver = new TemplateFileResolver();
        $templateFileResolver->appendToPathHierarchy(__DIR__ .  "/../../themes/parser");
        $engineRegistry = new EngineRegistry();
        $templateRenderer = new TemplateRenderer($engineRegistry, $templateFileResolver);
        $engineRegistry->registerEngine([".tpl.php"], new PhpEngineFactory($templateRenderer, new HelperVariable($templateRenderer, $templateFileResolver), new Janitor()));
        $templateEngine = new TemplateEngine($templateFileResolver, $templateRenderer);
        $tocGenerator = $this->getMockBuilder(TocGenerator::class)->disableOriginalConstructor()->getMock();
        $tocGenerator->method('anticipate')->willReturn('[TOC anticipated]');
        $eventDispatcher = $this->getMockBuilder(EventDispatcher::class)->getMock();
        $defaultTags = new DefaultTags($templateEngine, $tocGenerator, $eventDispatcher);

        $this->tagParser = new TagParser();
        foreach($defaultTags->getRegexMap() as $priority => $regex) {
            $this->tagParser->registerTag($regex['regex'], $priority, $regex['callable']);
        }
    }

    public function testTOC()
    {
        $parsed = $this->tagParser->parse("Some content [[_TOC_]] that I love");
        $this->assertEquals("Some content [TOC anticipated] that I love\n", $parsed);
    }

    public function testRenderImage()
    {
        $parsed = $this->tagParser->parse("[[something.jpeg]]");
        $this->assertEquals("<img src=\"np_images/something.jpeg\"/>\n", $parsed);
    }

    public function testRenderMultiImage()
    {
        $parsed = $this->tagParser->parse("[[something.jpeg, another.png]]");
        var_dump($parsed);
    }
}
