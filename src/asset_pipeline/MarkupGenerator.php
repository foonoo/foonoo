<?php


namespace foonoo\asset_pipeline;


interface MarkupGenerator
{
    public function generateMarkup(array $processed, string $sitePath): string;
}