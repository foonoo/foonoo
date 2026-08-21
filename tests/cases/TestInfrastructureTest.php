<?php

namespace foonoo\tests;

use foonoo\text\TagParser;
use foonoo\text\TemplateEngine;
use ntentan\panie\Container;

class TestInfrastructureTest extends BaseTestCase
{
    public function testVfsCreationAndFileOperations(): void
    {
        $dir = $this->createVfsDirectory('sites/default');
        $this->assertDirectoryExists($dir);

        $filePath = $this->createVfsFile('sites/default/index.md', '# Hello Foonoo');
        $this->assertFileExists($filePath);
        $this->assertSame('# Hello Foonoo', file_get_contents($filePath));
        $this->assertStringStartsWith('vfs://', $filePath);
    }

    public function testContainerFactory(): void
    {
        $container = $this->getTestContainer();
        $this->assertInstanceOf(Container::class, $container);

        $tagParser = $container->get(TagParser::class);
        $this->assertInstanceOf(TagParser::class, $tagParser);

        $templateEngine = $container->get(TemplateEngine::class);
        $this->assertInstanceOf(TemplateEngine::class, $templateEngine);
    }
}
