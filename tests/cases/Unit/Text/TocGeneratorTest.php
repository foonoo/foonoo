<?php

namespace foonoo\tests\Unit\Text;

use foonoo\events\EventDispatcher;
use foonoo\tests\BaseTestCase;
use foonoo\text\TemplateEngine;
use foonoo\text\TocGenerator;

class TocGeneratorTest extends BaseTestCase
{
    private EventDispatcher $eventDispatcher;
    private TemplateEngine $templateEngine;
    private TocGenerator $tocGenerator;

    public function setUp(): void
    {
        parent::setUp();
        $this->eventDispatcher = $this->createStub(EventDispatcher::class);
        $this->templateEngine = $this->createStub(TemplateEngine::class);
        $this->tocGenerator = new TocGenerator($this->eventDispatcher, $this->templateEngine);
    }

    public function testCreateContainer(): void
    {
        $container = $this->tocGenerator->createContainer('content-page-1');
        $this->assertSame("<div class='fn-toc' fntoc='content-page-1'/>", $container);
    }

    public function testGlobalTOCEmptyInitially(): void
    {
        $this->assertSame([], $this->tocGenerator->getGlobalTOC());
    }
}
