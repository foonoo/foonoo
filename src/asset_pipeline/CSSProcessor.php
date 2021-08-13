<?php


namespace foonoo\asset_pipeline;


use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\Minify;

class CSSProcessor extends MinifiableProcessor
{
    public function getMinifier(): Minify
    {
        return new CSS();
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
