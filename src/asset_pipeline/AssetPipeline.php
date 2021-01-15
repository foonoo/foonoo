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
    private $files = [];
    private $bundles = [];
    private $outputPath;
    private $processors = [];

    public function registerProcessor(string $type, Processor $processor)
    {
        if (!isset($this->processors[$type])) {
            $this->processors[$type] = [];
        }
        $this->processors[$type][] = $processor;
    }

    /**
     * Add an item to the pipeline.
     *
     * @param string $path
     * @param string $type
     * @param object $options
     * @throws FileNotFoundException
     */
    private function addItem(string $path, string $type, array $options): void
    {
        Filesystem::checkExists($path);
        $options['order'] = $options['order'] ?? 1;
        $options['mode'] = ($options['inline'] ?? false) ? 'inline' : 'external';
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

//    private function minify(Minify $minifier, string $script): string
//    {
//        $minifier->execute();
//        $minifier->add($script);
//        return $minifier->minify();
//    }

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

    private function buildItems(array $collection, string $extension, callable $postProcess = null): void
    {
        foreach ($collection as $bundle => $items) {
            $output = [];
            $buffers = ['inline' => '', 'external' => ''];
            usort($items, function ($a, $b) {
                return $a['options']['order'] > $b['options']['order'];
            });

            foreach ($items as $item) {
                $buffers[$item['options']['mode']] .= file_get_contents($item['path']);
            }

            if ($buffers['external']) {
                $assetPath = "assets/$extension/bundle-{$bundle}.$extension";
                $fullPath = "{$this->outputPath}{$assetPath}";
                Filesystem::directory(dirname($fullPath))->createIfNotExists(true);
                Filesystem::file($fullPath)->putContents($postProcess($buffers['external']));
                $output['external'] = ['path' => $assetPath];
            }

            if ($buffers['inline']) {
                $output['inline'] = ['contents' => $postProcess($buffers['inline'])];
            }

            if (!isset($this->bundles[$bundle])) {
                $this->bundles[$bundle] = [];
            }
            $this->bundles[$bundle][$extension] = $output;
        }
    }

    private function wrapInlineJs($script)
    {
        return "<script type='application/javascript'>{$script['contents']}</script>";
    }

    private function wrapExternalJs($script, $sitePath)
    {
        return "<script type='application/javascript' src='{$sitePath}{$script['path']}'></script>";
    }

    private function wrapInlineCss($script, $sitePath)
    {
        return "<style>{$script['contents']}</style>";
    }

    private function wrapExternalCss($script, $sitePath)
    {
        return "<link rel='stylesheet' href='{$sitePath}{$script['path']}' />";
    }

    private function generateMarkup($sitePath, $wrappers): array
    {
    }

    private function copyFiles()
    {
        foreach ($this->files as $file) {
            $destination = "$this->outputPath/{$file['options']['destination']}";
            $f = Filesystem::get($file['path']);
            Filesystem::directory(dirname($destination))->createIfNotExists(true);
            $f->copyTo($destination);
        }
    }

    public function buildAssets(): void
    {
        return;
//        $this->buildItems(
//            $this->javaScripts, 'js',
//            function (string $contents) {
//                return $this->minify($this->jsMinifier, $contents);
//            }
//        );
//        $this->buildItems(
//            $this->stylesheets, 'css',
//            function (string $contents) {
//                return $this->minify($this->cssMinifier, $contents);
//            }
//        );
//        $this->copyFiles();
    }

    public function getMarkup(string $sitePath): array
    {
        $wrappers = [
            'js' => ['inline' => [$this, 'wrapInternalJs'], 'external' => [$this, 'wrapExternalJs']],
            'css' => ['inline' => [$this, 'wrapInternalCss'], 'external' => [$this, 'wrapExternalCss']]
        ];
        $markups = [];
        foreach ($this->bundles as $bundle => $types) {
            $markup = '';
            foreach ($types as $type => $assets) {
                foreach ($assets as $target => $asset) {
                    $markup .= $wrappers[$type][$target]($asset, $sitePath);
                }
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
                } else {
                    $path = $item;
                    $options = [];
                }
                $itemPath = "$baseDirectory/$path";
                $this->addItem($itemPath, $type, $options);
            }
//            if(is_array($asset)) {
//                $path = array_key_first($asset);
//                $options = $asset[$path];
//            } else {
//                $path = $asset;
//                $options = [];
//            }
//            $this->ad
        }
//        $methods = [
//            'css' => [$this, 'addStylesheet'],
//            'js' => [$this, 'addJavascript'],
//            'files' => [$this, 'addFile']
//        ];
//        foreach(['js', 'css', 'files'] as $class) {
//            if(!isset($assets[$class])) {
//                continue;
//            }
//            foreach($assets[$class] as $index => $asset) {
//                if(is_array($asset)) {
//                    $path = array_key_first($asset);
//                    $options = $asset[$path];
//                } else {
//                    $path = $asset;
//                    $options = [];
//                }
//                $assetPath = "$baseDirectory/$path";
//                $methods[$class]($assetPath, $options);
//            }
//        }
    }

    public function setOutputPath(string $outputPath)
    {
        $this->outputPath = $outputPath;
    }
}
