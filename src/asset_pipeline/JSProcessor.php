<?php


namespace foonoo\asset_pipeline;


use MatthiasMullie\Minify\JS;
use MatthiasMullie\Minify\Minify;

class JSProcessor extends MinifiableProcessor
{
    public function createMinifier(): Minify
    {
        return new JS();
    }
}