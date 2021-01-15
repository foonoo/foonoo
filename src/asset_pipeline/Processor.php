<?php


namespace foonoo\asset_pipeline;


interface Processor
{
    public function process(string $content): string;
}