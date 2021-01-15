<?php


namespace foonoo\sites;


use foonoo\asset_pipeline\AssetPipeline;
use foonoo\content\BlogContentFactory;

class BlogSiteFactory implements SiteFactoryInterface
{
    private $blogContentFactory;
    private $assetPipeline;

    public function __construct(BlogContentFactory $blogContentFactory, AssetPipeline $assetPipeline)
    {
        $this->blogContentFactory = $blogContentFactory;
        $this->assetPipeline = $assetPipeline;
    }

    public function create(array $metadata, string $path): AbstractSite
    {
        $site = new BlogSite($this->blogContentFactory);
        $site->setAssetPipeline($this->assetPipeline);
        return $site;
    }
}