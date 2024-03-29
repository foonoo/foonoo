<?php


namespace foonoo\asset_pipeline;

use foonoo\events\EventDispatcher;
use foonoo\events\SiteWriteStarted;
use foonoo\sites\AbstractSite;
use MatthiasMullie\Minify\Minify;
use ntentan\utils\Filesystem;

abstract class MinifiableProcessor implements Processor, MarkupGenerator
{
    /**
     * The site for which minifiable content is being created.
     * @var AbstractSite
     */
    private $site;
    
    /**
     * Buffers holding the minified contents.
     * @var array
     */
    private $buffers = [];
    protected $glue = "";

    public function __construct(EventDispatcher $eventDispatcher) {
        $eventDispatcher->addListener(SiteWriteStarted::class, function(SiteWriteStarted $event) {
            $this->site = $event->getSite();
        });
    }

    public function process(string $path, array $options): array
    {
        $minifier = $this->getMinifier();
        $minifier->add($path);
        return [
            'processed' => $minifier->minify(),
            'asset_type' => $options['asset_type'],
            'target' => isset($options['inline']) && $options['inline'] === true ? 'inline' : 'external',
            'order' => $options['order'] ?? 1
        ];
    }

    private function createBuffersIfNotExists(array $processed): string
    {
        if(empty($processed)) {
            return "";
        }
        $bundle = $processed[0]['bundle'];
        if(isset($this->buffers[$bundle])) {
            return $bundle;
        }
        $this->buffers[$bundle] = ['inline' => '', 'external' => ''];
        usort($processed, fn ($a, $b) => $a['order'] - $b['order']);
        foreach ($processed as $item) {
            $this->buffers[$bundle][$item['target']] = "{$this->buffers[$bundle][$item['target']]}{$item['processed']}{$this->glue}";
        }

        if($this->buffers[$bundle]['external'] !== '') {
            $extension = $this->getExtension();
            $assetPath = "assets/$extension/bundle-{$bundle}.$extension";
            $fullPath = $this->site->getDestinationPath($assetPath);
            Filesystem::directory(dirname($fullPath))->createIfNotExists(true);
            Filesystem::file($fullPath)->putContents($this->buffers[$bundle]['external']);
        }

        return $bundle;
    }

    public function generateMarkup(array $processed, string $sitePath): string
    {
        $bundle = $this->createBuffersIfNotExists($processed);
        $markup = '';
        if ($this->buffers[$bundle]['inline'] !== '') {
            $markup = $this->wrapInline($this->buffers['inline']);
        }
        if($this->buffers[$bundle]['external'] !== '') {
            $extension = $this->getExtension();
            $assetPath = "assets/$extension/bundle-{$bundle}.$extension";
            $markup .= $this->wrapExternal($assetPath, $sitePath);
        }
        return $markup;
    }
    
    protected abstract function getMinifier(): Minify;

    protected abstract function wrapInline(string $content): string;

    protected abstract function wrapExternal(string $content, string $sitePath): string;

    protected abstract function getExtension(): string;

}
