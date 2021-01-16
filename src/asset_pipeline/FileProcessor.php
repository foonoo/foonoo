<?php


namespace foonoo\asset_pipeline;


use foonoo\events\EventDispatcher;
use foonoo\events\SiteObjectCreated;
use ntentan\utils\Filesystem;

class FileProcessor implements Processor
{
    private $outputPath;

    public function __construct(EventDispatcher $eventDispatcher) {
        $eventDispatcher->addListener(SiteObjectCreated::class, function(SiteObjectCreated $event) {
            $this->outputPath = $event->getSite()->getDestinationPath();
        });
    }

    public function process(string $path, array $options): array
    {
        $destination = "$this->outputPath/{$options['param']}";
        $f = Filesystem::get($path);
        Filesystem::directory(dirname($destination))->createIfNotExists(true);
        $f->copyTo($destination);
        return [];
    }
}