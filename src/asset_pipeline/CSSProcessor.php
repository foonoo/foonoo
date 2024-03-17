<?php
namespace foonoo\asset_pipeline;


use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\Minify;
use foonoo\events\EventDispatcher;
use ScssPhp\ScssPhp\Compiler;
use ntentan\utils\Filesystem;
use ntentan\utils\exceptions\FileNotFoundException;

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
    
//    private
    
    /**
     * Process the CSS file or CSS content depending on what is attached. 
     * 
     * @param string $item
     * @param array $options
     * @return array
     */
    public function process(string $item, array $options): array
    {
        try {
            $file = (isset($options['base_directory']) ? $options['base_directory'] . DIRECTORY_SEPARATOR : '') . $item;
            Filesystem::checkExists($file);
            $includePath = pathinfo($file, PATHINFO_DIRNAME);
            $contents = file_get_contents($file);
        } catch (FileNotFoundException) {
            $contents = $item;
            $includePath = $options['base_directory'] ?? '.';
        }

        if (isset($options["include_path"])) {
            $includePaths = is_array($options["include_path"]) ? $options['include_path'] : [$options['include_path']];
            $includePaths = array_map(fn($x) => $options['base_directory'] . DIRECTORY_SEPARATOR . $x, $includePaths);
            foreach($includePaths as $path) {
                Filesystem::checkExists($path);
                $this->sassCompiler->addImportPath($path);    
            }
          }
                
        if($options['asset_type'] === "scss") {
            $this->sassCompiler->addImportPath($includePath);
            $contents = $this->sassCompiler->compileString($contents)->getCss();
            $options['asset_type'] = "css";
        }
        
        return parent::process($contents, $options);
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
