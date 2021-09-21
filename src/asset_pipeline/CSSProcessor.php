<?php


namespace foonoo\asset_pipeline;


use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\Minify;
use foonoo\events\EventDispatcher;
use ScssPhp\ScssPhp\Compiler;

class CSSProcessor extends MinifiableProcessor
{
    /**
     * An instance of the SASS compiler.
     * 
     * @var Compiler
     */
    private $sassCompiler;
    
    /**
     * Create a new CSSProcessor.
     * 
     * @param EventDispatcher $eventDispatcher
     * @param Compiler $sassCompiler
     */
    public function __construct(EventDispatcher $eventDispatcher, Compiler $sassCompiler)
    {
        parent::__construct($eventDispatcher);
        $this->sassCompiler = $sassCompiler;
    }
    
    /**
     * An instance of the Minifier.
     * 
     * @return Minify
     */
    public function getMinifier(): Minify
    {
        return new CSS();
    }
    
    /**
     * 
     * @param string $path
     * @param array $options
     * @return array
     */
    public function process(string $path, array $options): array
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if($extension === "scss") {
            $directory = pathinfo($path, PATHINFO_DIRNAME);
            $this->sassCompiler->addImportPath($directory);
            $path = $this->sassCompiler->compileString(file_get_contents($path))->getCss();
        }
        return parent::process($path, $options);
    }

    protected function wrapInline(string $content) : string
    {
        return "<style>{$content}</style>";
    }

    protected function wrapExternal(string $content, string $sitePath) : string
    {
        return "<link rel='stylesheet' href='{$sitePath}{$content}' />";
    }

    protected function getExtension(): string
    {
        return 'css';
    }
}
