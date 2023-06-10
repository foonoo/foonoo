<?php
namespace foonoo\tests;

use foonoo\events\ContentGenerationStarted;
use foonoo\events\EventDispatcher;
use foonoo\events\SiteWriteStarted;
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
        $engineRegistry->registerEngine(
            [".tpl.php"], 
            new PhpEngineFactory(
                $templateRenderer, new HelperVariable($templateRenderer, $templateFileResolver), new Janitor()
            )
        );
        $templateEngine = new TemplateEngine($templateFileResolver, $templateRenderer);
        $tocGenerator = $this->getMockBuilder(TocGenerator::class)->disableOriginalConstructor()->getMock();
        $tocGenerator->method('createContainer')->willReturn('[TOC anticipated]');
        //$eventDispatcher = $this->getMockBuilder(EventDispatcher::class)->getMock();
        
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->registerEventType(SiteWriteStarted::class, function($args) {});
        $eventDispatcher->registerEventType(ContentGenerationStarted::class, function($args) {});

        $defaultTags = new DefaultTags($templateEngine, $tocGenerator, $eventDispatcher);

        $this->tagParser = new TagParser();
        foreach($defaultTags->getRegexMap() as $priority => $regex) {
            $this->tagParser->registerTag($regex['regex'], $priority, $regex['callable'], $regex["name"]);
        }
    }

    public function testTOC()
    {
        $parsed = $this->tagParser->parse("Some content [[_TOC_]] that I love");
        $this->assertEquals("Some content [TOC anticipated] that I love", $parsed);
    }

    public function testRenderImage()
    {
        $parsed = $this->tagParser->parse("[[something.jpeg]]");
        $this->assertEquals("<img src=\"images/something.jpeg\" loading=\"lazy\" />", $parsed);
    }

    public function testRenderImageAlt()
    {
        $parsed = $this->tagParser->parse("[[ A description of something | something.jpeg ]]");
        $this->assertEquals("<img src=\"images/something.jpeg\" loading=\"lazy\" alt=\"A description of something \"/>", $parsed);
        $parsed = $this->tagParser->parse("[[ description| something.jpeg ]]");
        $this->assertEquals("<img src=\"images/something.jpeg\" loading=\"lazy\" alt=\"description\"/>", $parsed);
    }
}
