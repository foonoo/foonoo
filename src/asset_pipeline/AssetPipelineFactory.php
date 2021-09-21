<?php

namespace foonoo\asset_pipeline;

use foonoo\events\EventDispatcher;

/**
 * Description of AssetPipelineFactory
 *
 * @author ekow
 */
class AssetPipelineFactory
{
    private $eventDispatcher;
    
    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }
    
    public function create() : AssetPipeline
    {
        $pipeline = new AssetPipeline();
        $cssProcessor = new CSSProcessor($this->eventDispatcher, new \ScssPhp\ScssPhp\Compiler()); //$container->get(CSSProcessor::class);
        $jsProcessor = new JSProcessor($this->eventDispatcher);
        $pipeline->registerProcessor('css', $cssProcessor);
        $pipeline->registerProcessor('js', $jsProcessor);
        $pipeline->registerProcessor('files', new FileProcessor($this->eventDispatcher));
        $pipeline->registerMarkupGenerator('css', $cssProcessor);
        $pipeline->registerMarkupGenerator('js', $jsProcessor);
        return $pipeline;        
    }
}
