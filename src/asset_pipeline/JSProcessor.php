<?php


namespace foonoo\asset_pipeline;


use MatthiasMullie\Minify\JS;
use MatthiasMullie\Minify\Minify;

class JSProcessor extends MinifiableProcessor
{
    protected $glue = ";";

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
        return "<script type='application/javascript' src='{$sitePath}{$content}' async defer></script>";
    }

    protected function getExtension(): string
    {
        return 'js';
    }
}
