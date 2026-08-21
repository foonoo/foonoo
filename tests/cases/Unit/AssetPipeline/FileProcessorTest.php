<?php

namespace foonoo\tests\Unit\AssetPipeline;

use foonoo\asset_pipeline\FileProcessor;
use foonoo\events\EventDispatcher;
use foonoo\events\SiteWriteStarted;
use foonoo\sites\AbstractSite;
use foonoo\tests\BaseTestCase;

class FileProcessorTest extends BaseTestCase
{
    public function testProcessCopiesFileToDestination(): void
    {
        $inputDir = $this->createVfsDirectory('input');
        $outputDir = $this->createVfsDirectory('output');
        $this->createVfsFile('input/images/photo.png', 'binary_photo_data');

        $site = $this->createStub(AbstractSite::class);
        $site->method('getDestinationPath')->willReturn($outputDir);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->registerEventType(SiteWriteStarted::class, fn($args) => $args['event']);

        $processor = new FileProcessor($eventDispatcher);

        $siteWriteStartedEvent = $this->createStub(SiteWriteStarted::class);
        $siteWriteStartedEvent->method('getSite')->willReturn($site);

        $eventDispatcher->dispatch(SiteWriteStarted::class, ['event' => $siteWriteStartedEvent]);

        $processor->process('images/photo.png', ['base_directory' => $inputDir]);

        $expectedCopiedFile = $outputDir . '/assets/images/photo.png';
        $this->assertFileExists($expectedCopiedFile);
        $this->assertSame('binary_photo_data', file_get_contents($expectedCopiedFile));
    }
}
