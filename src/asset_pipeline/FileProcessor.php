<?php

namespace foonoo\asset_pipeline;

use foonoo\events\EventDispatcher;
use foonoo\events\SiteWriteStarted;
use ntentan\utils\Filesystem;

class FileProcessor implements Processor
{
    private $outputPath;

    public function __construct(EventDispatcher $eventDispatcher) {
        $eventDispatcher->addListener(SiteWriteStarted::class, 
            function(SiteWriteStarted $event) {
                $this->outputPath = $event->getSite()->getDestinationPath();
            }
        );
    }

    public function process(string $path, array $options): array
    {
        $destination = "$this->outputPath/assets/{$path}"; //{$options['param']}";
        $f = Filesystem::get((isset($options['base_directory']) ? "{$options['base_directory']}/" : ""). $path);
        Filesystem::directory(dirname($destination))->createIfNotExists(true);
        $f->copyTo($destination);
        return $options;
    }
}
