<?php

namespace foonoo\tests\Unit\AssetPipeline;

use foonoo\asset_pipeline\JSProcessor;
use foonoo\events\EventDispatcher;
use foonoo\tests\BaseTestCase;

class JSProcessorTest extends BaseTestCase
{
    private EventDispatcher $eventDispatcher;
    private JSProcessor $jsProcessor;

    public function setUp(): void
    {
        parent::setUp();
        $this->eventDispatcher = $this->createStub(EventDispatcher::class);
        $this->jsProcessor = new JSProcessor($this->eventDispatcher);
    }

    public function testProcessRawJsMinification(): void
    {
        $rawJs = "function hello(name) {\n    var greeting = 'Hello, ' + name;\n    console.log(greeting);\n}";
        $result = $this->jsProcessor->process($rawJs, ['asset_type' => 'js']);

        $this->assertSame('js', $result['asset_type']);
        $this->assertSame('external', $result['target']);
        $this->assertStringContainsString("function hello(name){var greeting='Hello, '+name;console.log(greeting)}", $result['processed']);
    }

    public function testProcessJsInlineOption(): void
    {
        $rawJs = "var x = 10; var y = 20;";
        $result = $this->jsProcessor->process($rawJs, ['asset_type' => 'js', 'inline' => true]);

        $this->assertSame('inline', $result['target']);
        $this->assertSame("var x=10;var y=20", $result['processed']);
    }

    public function testProcessJsFile(): void
    {
        $dir = $this->createVfsDirectory('js_assets');
        $this->createVfsFile('js_assets/app.js', 'let a = 1; let b = 2;');

        $result = $this->jsProcessor->process('app.js', [
            'base_directory' => $dir,
            'asset_type' => 'js'
        ]);

        $this->assertSame('let a=1;let b=2', $result['processed']);
    }
}
