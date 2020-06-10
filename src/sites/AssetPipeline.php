<?php

namespace foonoo\sites;


class AssetPipeline
{
    private $stylesheets = [];
    private $javascripts = [];
    private $images = [];
    private $options = [];
    private $markup;

    public function __construct($options)
    {
        $this->options = $options;
    }

    private function addItem($path, $item, $options) : void
    {
        $options['order'] = $options['order'] ?? 1;
        $options['mode'] = ($options['inline'] ?? false) ? 'inline' : 'external';
        $this->stylesheets[] = ['path' => $path, 'options' => $options];
    }

    public function addStylesheet($path, $options = []) : void
    {
        $this->addItem($path, $this->stylesheets, $options);
    }

    public function addJavascript($path, $options = []) : void
    {
        $this->addItem($path, $this->javascripts, $options);
    }

    public function addImage($path, $options) : void
    {

    }

    private function buildItems(array $items, callable $minify = null) : array
    {
        $buffer = '';
        $output = [];
        $currentMode = null;

        usort($items, function ($a, $b) { return $a['options']['order'] > $b['options']['order']; });

        foreach($items as $item) {
            if($item['options']['mode'] != $currentMode && strlen($buffer) > 0) {
                $output[] = ['mode' => $currentMode, 'buffer' => $buffer];
            }
            $content = file_get_contents($item['path']);
            if($minify) {
                $content = $minify($content);
            }
            $buffer .= $content;
        }
        return $output;
    }

    private function build() : void
    {
        $js = $this->buildItems($this->javascripts);
        $css = $this->buildItems($this->stylesheets);
    }

    public function getMarkup()
    {
        if(!$this->markup) {
            $this->build();
        }
        return $this->markup;
    }

    public function merge($assets, $baseDirectory = null)
    {
        $methods = [
            'js' => [$this, 'addStylesheet'],
            'css' => [$this, 'addJavascript']
        ];
        foreach(['js', 'css', 'images'] as $class) {
            if(!isset($assets[$class])) {
                continue;
            }
            foreach($assets[$class] as $path => $options) {
                var_dump($path, $options);
            }
        }
    }
}
