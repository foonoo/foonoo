<?php

namespace foonoo\sites;


use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use MatthiasMullie\Minify\Minify;
use ntentan\utils\Filesystem;

class AssetPipeline
{
    private $stylesheets = [];
    private $javascripts = [];
    private $files = [];
    private $builtStylesheets = [];
    private $builtJavascripts = [];
    private $sitePath;
    private $destinationPath;
    private $cssMinifier;
    private $jsMinifier;

    public function __construct(CSS $cssMinifier, JS $jsMinifier)
    {
        $this->cssMinifier = $cssMinifier;
        $this->jsMinifier = $jsMinifier;
    }

    private function addItem($path, $options, &$collection) : void
    {
        Filesystem::checkExists($path);
        $options['order'] = $options['order'] ?? 1;
        $options['mode'] = ($options['inline'] ?? false) ? 'inline' : 'external';
        $collection[] = ['path' => $path, 'options' => $options];
    }

    public function addStylesheet($path, $options = []) : void
    {
        $this->addItem($path, $options, $this->stylesheets);
    }

    public function addJavascript($path, $options = []) : void
    {
        $this->addItem($path, $options, $this->javascripts,);
    }

    private function minify(Minify $minifier, string $script) : string
    {
        $minifier->execute();
        $minifier->add($script);
        return $minifier->minify();
    }

    public function addFile($path, $options) : void
    {
        if(!is_array($options)) {
            $options = ['destination' => $options];
        }
        $this->files[] = ['path' => $path, 'options' => $options];
    }

    private function writeBuffer($buffer, $currentMode, $extension, &$written)
    {
        if(strlen($buffer) > 0) {
            if($currentMode == 'external') {
                $assetPath = "assets/$extension/bundle-{$written}.$extension";
                $fullPath = "$this->destinationPath$assetPath";
                Filesystem::directory(dirname($fullPath))->createIfNotExists(true);
                Filesystem::file($fullPath)->putContents($buffer);
                $written += 1;
                return ['mode' => $currentMode, 'contents' => $assetPath];
            } else {
                return ['mode' => $currentMode, 'contents' => $buffer];
            }
        }
    }

    private function buildItems(array $items, string $extension, callable $postProcess = null) : array
    {
        $buffer = '';
        $output = [];
        $currentMode = null;
        $written = 0;

        usort($items, function ($a, $b) { return $a['options']['order'] > $b['options']['order']; });

        foreach($items as $item) {
            if($currentMode !== null && $item['options']['mode'] != $currentMode) {
                $output[] = $this->writeBuffer($buffer, $currentMode, $extension, $written);
                $buffer = '';
                $currentMode = $item['options']['mode'];
            } else {
                $currentMode = $item['options']['mode'];
            }

            $content = file_get_contents($item['path']);
            $buffer .= "$content\n";
        }

        if($postProcess) {
            $buffer = $postProcess($buffer);
        }
        
        $finalBuffer = $this->writeBuffer($buffer, $currentMode, $extension, $written);
        if($finalBuffer) {
            $output[] = $finalBuffer;
        }
        return $output;
    }

    private function wrapInlineJs($script)
    {
        return "<script type='application/javascript'>{$script['contents']}</script>";
    }

    private function wrapExternalJs($script, $sitePath)
    {
        return "<script type='application/javascript' src='{$sitePath}{$script['contents']}'></script>";
    }

    private function wrapInlineCss($script, $sitePath)
    {
        return "<style>{$script['contents']}</style>";
    }

    private function wrapExternalCss($script, $sitePath)
    {
        return "<link rel='stylesheet' href='{$sitePath}{$script['contents']}' />";
    }

    private function generateMarkup($items, $sitePath, $externalWrapper, $inlineWrapper) : string
    {
        $markup = "";
        $wrappers = ['inline' => $inlineWrapper, 'external' => $externalWrapper];
        foreach ($items as $script) {
            $markup .= $wrappers[$script['mode']]($script, $sitePath);
        }
        return $markup;
    }

    private function copyFiles()
    {
        foreach ($this->files as $file) {
            $destination = "$this->destinationPath/{$file['options']['destination']}";
            $f = Filesystem::get($file['path']);
            Filesystem::directory(dirname($destination))->createIfNotExists(true);
            $f->copyTo($destination);
        }
    }

    public function buildAssets() : void
    {
        $this->builtJavascripts = $this->buildItems(
            $this->javascripts, 'js',
            function(string $contents) { return $this->minify($this->jsMinifier, $contents); }
        );
        $this->builtStylesheets = $this->buildItems(
            $this->stylesheets, 'css',
            function(string $contents) { return $this->minify($this->cssMinifier, $contents); }
        );
        $this->copyFiles();
    }

    public function getMarkup($sitePath)
    {
        return $this->generateMarkup($this->builtJavascripts, $sitePath, [$this, 'wrapExternalJs'], [$this, 'wrapInternalJs'])
             . $this->generateMarkup($this->builtStylesheets, $sitePath, [$this, 'wrapExternalCss'], [$this, 'wrapInternalCss']);
    }

    public function merge($assets, $baseDirectory = null)
    {
        $methods = [
            'css' => [$this, 'addStylesheet'],
            'js' => [$this, 'addJavascript'],
            'files' => [$this, 'addFile']
        ];
        foreach(['js', 'css', 'files'] as $class) {
            if(!isset($assets[$class])) {
                continue;
            }
            foreach($assets[$class] as $index => $asset) {
                if(is_array($asset)) {
                    $path = array_key_first($asset);
                    $options = $asset[$path];
                } else {
                    $path = $asset;
                    $options = [];
                }
                $assetPath = "$baseDirectory/$path";
                $methods[$class]($assetPath, $options);
            }
        }
    }

    public function setSitePaths(string $destinationPath, string $sitePath)
    {
        $this->sitePath = $sitePath;
        $this->destinationPath = $destinationPath;
    }
}
