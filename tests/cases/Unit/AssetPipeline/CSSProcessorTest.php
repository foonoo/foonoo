<?php

namespace foonoo\tests\Unit\AssetPipeline;

use foonoo\asset_pipeline\CSSProcessor;
use foonoo\events\EventDispatcher;
use foonoo\tests\BaseTestCase;
use ScssPhp\ScssPhp\Compiler;

class CSSProcessorTest extends BaseTestCase
{
    private EventDispatcher $eventDispatcher;
    private CSSProcessor $cssProcessor;

    public function setUp(): void
    {
        parent::setUp();
        $this->eventDispatcher = $this->createStub(EventDispatcher::class);
        $this->cssProcessor = new CSSProcessor($this->eventDispatcher, new Compiler());
    }

    public function testProcessRawCssMinification(): void
    {
        $rawCss = "body {\n    background-color: #fff;\n    color: #000;\n}";
        $result = $this->cssProcessor->process($rawCss, ['asset_type' => 'css']);

        $this->assertSame('css', $result['asset_type']);
        $this->assertSame('external', $result['target']);
        $this->assertSame('body{background-color:#fff;color:#000}', $result['processed']);
    }

    public function testProcessInlineOption(): void
    {
        $rawCss = "h1 { font-size: 2rem; }";
        $result = $this->cssProcessor->process($rawCss, ['asset_type' => 'css', 'inline' => true]);

        $this->assertSame('inline', $result['target']);
        $this->assertSame('h1{font-size:2rem}', $result['processed']);
    }

    public function testProcessScss(): void
    {
        $rawScss = "\$primary: #ff0000; .button { color: \$primary; &:hover { color: #000; } }";
        $result = $this->cssProcessor->process($rawScss, ['asset_type' => 'scss']);

        $this->assertSame('css', $result['asset_type']);
        $this->assertSame('.button{color:red}.button:hover{color:#000}', $result['processed']);
    }

    public function testProcessCssFile(): void
    {
        $dir = $this->createVfsDirectory('css_assets');
        $this->createVfsFile('css_assets/style.css', 'div { margin: 0px; }');

        $result = $this->cssProcessor->process('style.css', [
            'base_directory' => $dir,
            'asset_type' => 'css'
        ]);

        $this->assertSame('div{margin:0}', $result['processed']);
    }
}
