<?php


namespace foonoo\asset_pipeline;


use MatthiasMullie\Minify\JS;
use MatthiasMullie\Minify\Minify;
use ntentan\utils\Filesystem;
use ntentan\utils\exceptions\FileNotFoundException;


class JSProcessor extends MinifiableProcessor
{
    protected $glue = ";\n\n";

    public function getMinifier(): Minify
    {
        return new JS();
    }

    protected function wrapInline(string $content): string
    {
        return "<script type='application/javascript'>{$content}</script>";
    }

    protected function wrapExternal(string $content, string $sitePath): string
    {
        return "<script type='application/javascript' src='{$sitePath}{$content}' defer></script>";
    }

    protected function getExtension(): string
    {
        return 'js';
    }
    
    public function process(string $item, array $options): array
    {
        try {
            $file = (isset($options['base_directory']) ? $options['base_directory'] . DIRECTORY_SEPARATOR : '') . $item;
            Filesystem::checkExists($file);
            $contents = file_get_contents($file);
        } catch (FileNotFoundException $_) {
            $contents = $item;
        }
                
        return parent::process($contents, $options);
        
    }
}
