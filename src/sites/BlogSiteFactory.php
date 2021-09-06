<?php


namespace foonoo\sites;


use foonoo\asset_pipeline\AssetPipelineFactory;
use foonoo\content\BlogContentFactory;

class BlogSiteFactory implements SiteFactoryInterface
{
    private $blogContentFactory;
    private $assetPipelineFactory;

    public function __construct(BlogContentFactory $blogContentFactory, AssetPipelineFactory $assetPipelineFactory)
    {
        $this->blogContentFactory = $blogContentFactory;
        $this->assetPipelineFactory = $assetPipelineFactory;
    }

    public function create(array $metadata, string $path): AbstractSite
    {
        $site = new BlogSite($this->blogContentFactory);
        $site->setAssetPipeline($this->assetPipelineFactory->create());
        return $site;
    }
}
