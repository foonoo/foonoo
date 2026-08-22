<?php

namespace foonoo\tests\Integration\Sites;

use foonoo\sites\BlogSiteFactory;
use foonoo\sites\SiteWriter;
use foonoo\tests\BaseTestCase;

class BlogSiteTest extends BaseTestCase
{
    public function testGenerateBlogSiteWithPostsAndListings(): void
    {
        $inputDir = $this->createVfsDirectory('blog_input');
        $outputDir = $this->createVfsDirectory('blog_output');

        // Create blog posts in posts/
        $this->createVfsFile(
            'blog_input/posts/2026-08-01-first-post.md',
            "---\ntitle: First Blog Post\ntags:\n  - tech\n---\n# First Post\nHello world from the first blog post."
        );
        $this->createVfsFile(
            'blog_input/posts/2026-08-15-second-post.md',
            "---\ntitle: Second Blog Post\ntags:\n  - tech\n  - updates\n---\n# Second Post\nHello world from the second blog post."
        );

        $container = $this->getTestContainer();

        /** @var BlogSiteFactory $blogFactory */
        $blogFactory = $container->get(BlogSiteFactory::class);
        $site = $blogFactory->create();
        $site->setPath('');
        $site->setSourceRoot($inputDir . '/');
        $site->setDestinationRoot($outputDir . '/');
        $site->setMetaData([
            'title' => 'Tech Chronicles',
            'type' => 'blog',
            'taxonomies' => ['tags' => 'Tags'],
            'excluded_paths' => ['*/site.yml', '*/site.yaml', '*/_foonoo*']
        ]);
        $site->setAssetPipeline($container->get(\foonoo\asset_pipeline\AssetPipelineFactory::class)->create());
        $cacheFactory = new \foonoo\utils\CacheFactory();
        $site->setCache($cacheFactory->create($this->createVfsDirectory('cache')));

        /** @var SiteWriter $siteWriter */
        $siteWriter = $container->get(SiteWriter::class);
        $siteWriter->write($site);

        // Verify individual posts generated at dated URLs
        $post1Path = $outputDir . '/2026/08/01/first-post.html';
        $this->assertFileExists($post1Path);
        $post1Html = file_get_contents($post1Path);
        $this->assertStringContainsString('First Post', $post1Html);
        $this->assertStringContainsString('Hello world from the first blog post.', $post1Html);

        $post2Path = $outputDir . '/2026/08/15/second-post.html';
        $this->assertFileExists($post2Path);
        $post2Html = file_get_contents($post2Path);
        $this->assertStringContainsString('Second Post', $post2Html);
        $this->assertStringContainsString('Hello world from the second blog post.', $post2Html);

        // Verify index.html blog listing
        $indexPath = $outputDir . '/index.html';
        $this->assertFileExists($indexPath);
        $indexHtml = file_get_contents($indexPath);
        $this->assertStringContainsString('First Blog Post', $indexHtml);
        $this->assertStringContainsString('Second Blog Post', $indexHtml);

        // Verify posts.html
        $postsPath = $outputDir . '/posts.html';
        $this->assertFileExists($postsPath);

        // Verify taxonomy pages generated
        $techTagPath = $outputDir . '/tags/tech.html';
        $this->assertFileExists($techTagPath);
        $techTagHtml = file_get_contents($techTagPath);
        $this->assertStringContainsString('First Blog Post', $techTagHtml);
        $this->assertStringContainsString('Second Blog Post', $techTagHtml);
    }
}
