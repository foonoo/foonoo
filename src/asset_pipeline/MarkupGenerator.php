<?php


namespace foonoo\asset_pipeline;


/**
 * Interface for classes that generate markups for assets.
 * 
 * @author Ekow Abaka
 *
 */
interface MarkupGenerator
{
    public function generateMarkup(array $processed, string $sitePath): string;
}