<?php


namespace foonoo\asset_pipeline;

use MatthiasMullie\Minify\Minify;

abstract class MinifiableProcessor implements Processor
{
    public abstract function createMinifier() : Minify;

    public function process(string $content): string
    {
        $minifier = $this->createMinifier();
        $minifier->add($content);
        return $minifier->minify();
    }
}