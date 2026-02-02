<?php

namespace foonoo\asset_pipeline;

use foonoo\events\EventDispatcher;
use clearice\io\Io;
use ScssPhp\ScssPhp\Compiler;

/**
 * Description of AssetPipelineFactory
 *
 * @author ekow
 */
class AssetPipelineFactory
{
    private EventDispatcher $eventDispatcher;
    private Io $io;
    
    public function __construct(EventDispatcher $eventDispatcher, Io $io)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->io = $io;
    }
    
    public function create() : AssetPipeline
    {
        $pipeline = new AssetPipeline($this->io);
        $cssProcessor = new CSSProcessor($this->eventDispatcher, new Compiler());
        $jsProcessor = new JSProcessor($this->eventDispatcher);
        $pipeline->registerProcessor('css', $cssProcessor);
        $pipeline->registerProcessor('js', $jsProcessor);
        $pipeline->registerProcessor('files', new FileProcessor($this->eventDispatcher));
        $pipeline->registerProcessor('scss', $cssProcessor);
        $pipeline->registerMarkupGenerator('css', $cssProcessor);
        $pipeline->registerMarkupGenerator('js', $jsProcessor);
        return $pipeline;        
    }
}
