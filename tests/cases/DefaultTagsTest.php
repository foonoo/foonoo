<?php
namespace foonoo\tests;

use foonoo\content\Content;
use foonoo\events\ContentGenerationStarted;
use foonoo\events\EventDispatcher;
use foonoo\events\SiteWriteStarted;
use foonoo\sites\AbstractSite;
use foonoo\text\DefaultTags;
use foonoo\text\TagParser;
use foonoo\text\TemplateEngine;
use foonoo\text\TocGenerator;
use foonoo\theming\ThemeManager;
use ntentan\honam\EngineRegistry;
use ntentan\honam\engines\php\HelperVariable;
use ntentan\honam\engines\php\Janitor;
use ntentan\honam\factories\PhpEngineFactory;
use ntentan\honam\TemplateFileResolver;
use ntentan\honam\TemplateRenderer;
use PHPUnit\Framework\TestCase;

class DefaultTagsTest extends TestCase
{
    private TagParser $tagParser;
    private EventDispatcher $eventDispatcher;

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
        
        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->registerEventType(SiteWriteStarted::class, function($args) {
            $site = $this->createStub(AbstractSite::class);
            $templateEngine = $this->createStub(TemplateEngine::class);
            $themeManager = $this->createStub(ThemeManager::class);
            return new SiteWriteStarted($site, $templateEngine, $themeManager);
        });
        $this->eventDispatcher->registerEventType(ContentGenerationStarted::class, function($args) {
            $content = $this->createStub(Content::class);
            $content->method('getID')->willReturn("content-id");
            return new ContentGenerationStarted($content);
        });

        $defaultTags = new DefaultTags($templateEngine, $tocGenerator, $this->eventDispatcher);

        $this->tagParser = new TagParser();
        foreach($defaultTags->getRegexMap() as $priority => $regex) {
            $this->tagParser->registerTag($regex['regex'], $priority, $regex['callable'], $regex["name"]);
        }
    }

    public function testTOC()
    {
        $this->eventDispatcher->dispatch(SiteWriteStarted::class, []);
        $this->eventDispatcher->dispatch(ContentGenerationStarted::class, []);
        $parsed = $this->tagParser->parse("Some content [[_TOC_]] that I love");
        $this->assertEquals("Some content [TOC anticipated] that I love", $parsed);
    }

    public function testRenderImage()
    {
        $parsed = $this->tagParser->parse("[[something.jpeg]]");
        $this->assertEquals("<img src=\"images/something.jpeg\" loading=\"lazy\" >", $parsed);
    }

    public function testRenderImageAlt()
    {
        $parsed = $this->tagParser->parse("[[ A description of something | something.jpeg ]]");
        $this->assertEquals("<img src=\"images/something.jpeg\" loading=\"lazy\" alt=\"A description of something \">", $parsed);
        $parsed = $this->tagParser->parse("[[ description| something.jpeg ]]");
        $this->assertEquals("<img src=\"images/something.jpeg\" loading=\"lazy\" alt=\"description\">", $parsed);
    }
}
