<?php


namespace foonoo\asset_pipeline;


use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\Minify;

class CSSProcessor extends MinifiableProcessor
{
    public function createMinifier(): Minify
    {
        return new CSS();
    }
}