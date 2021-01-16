<?php

namespace foonoo\asset_pipeline;


use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use MatthiasMullie\Minify\Minify;
use ntentan\utils\exceptions\FileNotFoundException;
use ntentan\utils\Filesystem;

/**
 * Manages all the assets for a site.
 * Stylesheets and Javascripts added to the pipeline can be combined and minified. Other files are just copied to
 * specified destinations when sites are built.
 *
 * @package foonoo\sites
 */
class AssetPipeline
{
    private $items = [];
    private $builtItems = [];
    private $files = [];
    private $processors = [];
    private $markupGenerators = [];

    public function registerProcessor(string $type, Processor $processor)
    {
        if (!isset($this->processors[$type])) {
            $this->processors[$type] = [];
        }
        $this->processors[$type][] = $processor;
    }

    public function registerMarkupGenerator(string $type, MarkupGenerator $generator)
    {
        $this->markupGenerators[$type] = $generator;
    }

    /**
     * Add an item to the pipeline.
     *
     * @param string $path
     * @param string $type
     * @param array|string $options
     * @throws FileNotFoundException
     */
    private function addItem(string $path, string $type, array $options): void
    {
        Filesystem::checkExists($path);
        $bundles = $options['bundles'] ?? ["default"];
        unset($options['bundles']);
        foreach ($bundles as $bundle) {
            if (!isset($this->items[$bundle])) {
                $this->items[$bundle] = [$type => []];
            }
            if (!isset($this->items[$bundle][$type])) {
                $this->items[$bundle][$type] = [];
            }
            $this->items[$bundle][$type][] = ['path' => $path, 'options' => $options];
        }
    }

    /**
     * Add an arbitrary file to the pipeline.
     *
     * @param $path
     * @param $options
     */
    public function addFile($path, $options): void
    {
        if (!is_array($options)) {
            $options = ['destination' => $options];
        }
        $this->files[] = ['path' => $path, 'options' => $options];
    }

    public function buildAssets(): void
    {
        foreach($this->items as $bundle => $types) {
            $this->builtItems[$bundle] = [];
            foreach($types as $type => $items) {
                $processors = $this->processors[$type];
                $this->builtItems[$bundle][$type] = [];
                foreach($processors as $processor) {
                    foreach($items as $item) {
                        $processedItem = $processor->process($item['path'], $item['options']);
                        $processedItem['bundle'] = $bundle;
                        $this->builtItems[$bundle][$type][] = $processedItem;
                    }
                }
            }
        }
    }

    public function getMarkup(string $sitePath): array
    {
        $markups = [];
        foreach($this->builtItems as $bundle => $types) {
            $markup = '';
            foreach($types as $type => $items) {
                if(!isset($this->markupGenerators[$type]) || empty($this->markupGenerators[$type])) {
                    continue;
                }
                $markupGenerator = $this->markupGenerators[$type];
                $markup .= $markupGenerator->generateMarkup($items, $sitePath);
            }
            $markups[$bundle] = $markup;
        }
        return $markups;
    }

    public function merge(array $assets, string $baseDirectory = null): void
    {
        foreach ($assets as $type => $items) {
            foreach ($items as $index => $item) {
                if(is_array($item)) {
                    $path = array_key_first($item);
                    $options = $item[$path];
                    if(!is_array($options)) {
                        $options = ['param' => $options];
                    }
                } else {
                    $path = $item;
                    $options = [];
                }
                $itemPath = "$baseDirectory/$path";
                $this->addItem($itemPath, $type, $options);
            }
        }
    }
}
