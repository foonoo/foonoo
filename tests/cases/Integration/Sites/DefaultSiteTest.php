<?php

namespace foonoo\tests\Integration\Sites;

use foonoo\Builder;
use foonoo\sites\DefaultSiteFactory;
use foonoo\sites\SiteWriter;
use foonoo\tests\BaseTestCase;

class DefaultSiteTest extends BaseTestCase
{
    public function testGenerateDefaultSiteWithPagesAndAssets(): void
    {
        $inputDir = $this->createVfsDirectory('input');
        $outputDir = $this->createVfsDirectory('output');

        // Create site configuration
        $this->createVfsFile('input/site.yml', "title: My Awesome Site\ntype: default\n");

        // Create markdown pages
        $this->createVfsFile('input/index.md', "---\ntitle: Home\n---\n# Welcome to Foonoo\nThis is a static site.");
        $this->createVfsFile('input/about.md', "---\ntitle: About Us\n---\n# About Foonoo\nFoonoo is flexible.");

        // Create an asset
        $this->createVfsFile('input/robots.txt', "User-agent: *\nDisallow:");

        $container = $this->getTestContainer();
        
        /** @var DefaultSiteFactory $siteFactory */
        $siteFactory = $container->get(DefaultSiteFactory::class);
        $site = $siteFactory->create();
        $site->setPath('');
        $site->setSourceRoot($inputDir . '/');
        $site->setDestinationRoot($outputDir . '/');
        $site->setMetaData([
            'title' => 'My Awesome Site',
            'type' => 'default',
            'excluded_paths' => ['*/site.yml', '*/site.yaml', '*/_foonoo*']
        ]);
        $site->setAssetPipeline($container->get(\foonoo\asset_pipeline\AssetPipelineFactory::class)->create());
        $cacheFactory = new \foonoo\utils\CacheFactory();
        $site->setCache($cacheFactory->create($this->createVfsDirectory('cache')));

        /** @var SiteWriter $siteWriter */
        $siteWriter = $container->get(SiteWriter::class);
        $siteWriter->write($site);

        // Verify index.html generated
        $indexPath = $outputDir . '/index.html';
        $this->assertFileExists($indexPath);
        $indexHtml = file_get_contents($indexPath);
        $this->assertStringContainsString('<title>Home - My Awesome Site</title>', $indexHtml);
        $this->assertStringContainsString('<h1>Welcome to Foonoo</h1>', $indexHtml);
        $this->assertStringContainsString('<p>This is a static site.</p>', $indexHtml);

        // Verify about.html generated
        $aboutPath = $outputDir . '/about.html';
        $this->assertFileExists($aboutPath);
        $aboutHtml = file_get_contents($aboutPath);
        $this->assertStringContainsString('<title>About Us - My Awesome Site</title>', $aboutHtml);
        $this->assertStringContainsString('<h1>About Foonoo</h1>', $aboutHtml);

        // Verify asset copied
        $robotsPath = $outputDir . '/robots.txt';
        $this->assertFileExists($robotsPath);
        $this->assertSame("User-agent: *\nDisallow:", file_get_contents($robotsPath));
    }
}
