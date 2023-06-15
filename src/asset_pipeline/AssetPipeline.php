<?php

namespace foonoo\asset_pipeline;


use ntentan\utils\exceptions\FileNotFoundException;

/**
 * Manages all the assets for a site.
 * Stylesheets and Javascripts added to the pipeline can be combined and minified. Other files are just copied to
 * specified destinations when sites are built.
 * 
 * # Architecture
 * 
 */
class AssetPipeline
{
    private $items = [];
    private $builtItems = [];
    private $processors = [];
    private $markupGenerators = [];

    /**
     * Register a processor with the asset pipeline.
     */
    public function registerProcessor(string $type, Processor $processor)
    {
        if (!isset($this->processors[$type])) {
            $this->processors[$type] = [];
        }
        $this->processors[$type][] = $processor;
    }

    /**
     * Register a markup generator with the asset pipeline.
     */
    public function registerMarkupGenerator(string $type, MarkupGenerator $generator)
    {
        $this->markupGenerators[$type] = $generator;
    }

    /**
     * Add an item to the pipeline.
     *
     * @param string $item
     * @param string $type
     * @param array|string $options
     * @throws FileNotFoundException
     */
    public function addItem(string $item, string $type, array $options=[]): void
    {
        $bundles = $options['parameters']['bundles'] ?? ["default"];
        $options['priority'] = $options['priority'] ?? 0;
        unset($options['bundles']);
        foreach ($bundles as $bundle) {
            if (!isset($this->items[$bundle])) {
                $this->items[$bundle] = [$type => []];
            }
            if (!isset($this->items[$bundle][$type])) {
                $this->items[$bundle][$type] = [];
            }
            $this->items[$bundle][$type][] = ['contents' => $item, 'options' => $options];
        }
    }
    
    /**
     * Replace an item in the asset pipeline.
     * 
     * @param string $contents The original contents
     * @param string $newContents The contents to be replaced
     * @param string $type The type of content
     * @param array $options The options for the pipeline entry
     * @return void
     */
    public function replaceItem(string $contents, string $newContents, string $type, array $options=[]): void
    {
        //$bundles = $options['bundles'] ?? ["default"];
        $options['priority'] = $options['priority'] ?? 0;
        unset($options['bundles']);
        foreach($this->items as $bundle => $types) {
            foreach($types as $type => $items) {
                foreach($items as $key => $item) {
                    if($item['contents'] == $contents) {
                        $this->items[$bundle][$type][$key] = ['contents' => $newContents, 'options' => $options];
                        return;
                    }
                }
            }
        }
    }

    public function buildAssets(): void
    {
        foreach($this->items as $bundle => $types) {
            $this->builtItems[$bundle] = [];
            foreach($types as $type => $items) {
                $processors = $this->processors[$type];
                foreach($processors as $processor) {
                    usort($items, function($a, $b) { return $b['options']['priority'] - $a['options']['priority'];});
                    foreach($items as $item) {
                        $options = $item['options'];
                        $options['asset_type'] = $type;
                        $processedItem = $processor->process($item['contents'], $options);
                        $processedItem['bundle'] = $bundle;
                        $this->builtItems[$bundle][$processedItem['asset_type']][] = $processedItem;
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
        foreach ($assets as $type => $definition) {
            $options = $definition;
            $items = $options["items"];
            unset($options["items"]);
            foreach ($items as $item) {
                if(is_array($item)) {
                    $contents = array_key_first($item);
                    $parameters = $item[$contents];
                    $options['parameters'] = $item[$contents];
                    // if(is_array($)) {
                    //     $options['param'] = $item[$contents];
                    // } else {
                    //     $options['param' = 
                    // }
                } else {
                    $contents = $item;
                }
                if(isset($baseDirectory)) {
                    $options['base_directory'] = $baseDirectory;
                }
                $this->addItem($contents, $type, $options);
            }
        }
    }
}
