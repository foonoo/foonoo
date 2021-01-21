<?php


namespace foonoo\asset_pipeline;

use foonoo\events\EventDispatcher;
use foonoo\events\SiteObjectCreated;
use foonoo\sites\AbstractSite;
use MatthiasMullie\Minify\Minify;
use ntentan\utils\Filesystem;

abstract class MinifiableProcessor implements Processor, MarkupGenerator
{
    /**
     * @var AbstractSite
     */
    private $site;

    protected abstract function getMinifier(): Minify;

    protected abstract function wrapInline(string $content): string;

    protected abstract function wrapExternal(string $content, string $sitePath): string;

    protected abstract function getExtension(): string;

    public function __construct(EventDispatcher $eventDispatcher) {
        $eventDispatcher->addListener(SiteObjectCreated::class, function(SiteObjectCreated $event) {
            $this->site = $event->getSite();
        });
    }

    public function process(string $path, array $options): array
    {
        $minifier = $this->getMinifier();
        $minifier->add($path);
        return [
            'processed' => $minifier->minify(),
            'target' => isset($options['inline']) && $options['inline'] === true ? 'inline' : 'external',
            'order' => $options['order'] ?? 1
        ];
    }

    public function generateMarkup(array $processed, string $sitePath): string
    {
        $buffers = ['inline' => '', 'external' => ''];
        usort($processed, function ($a, $b) {
            return $a['order'] > $b['order'];
        });
        foreach ($processed as $item) {
            $buffers[$item['target']] .= $item['processed'];
        }
        $markup = '';
        $bundle = $item['bundle'];
        if ($buffers['inline'] !== '') {
            $markup .= $this->wrapInline($buffers['inline']);
        }
        if($buffers['external'] !== '') {
            $extension = $this->getExtension();
            $assetPath = "assets/$extension/bundle-{$bundle}.$extension";
            $fullPath = $this->site->getDestinationPath($assetPath);
            if(!file_exists($fullPath)) {
                Filesystem::directory(dirname($fullPath))->createIfNotExists(true);
                Filesystem::file($fullPath)->putContents($buffers['external']);git stat
            }
            $markup .= $this->wrapExternal($assetPath, $sitePath);
        }
        return $markup;
    }
}
