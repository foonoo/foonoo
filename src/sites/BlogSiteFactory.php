<?php


namespace foonoo\sites;


//use foonoo\asset_pipeline\AssetPipelineFactory;
use foonoo\content\BlogContentFactory;
use clearice\io\Io;

class BlogSiteFactory implements SiteFactoryInterface
{
    private $blogContentFactory;
    //private $assetPipelineFactory;
    private $io;

    public function __construct(BlogContentFactory $blogContentFactory, Io $io)
    {
        $this->blogContentFactory = $blogContentFactory;
        //$this->assetPipelineFactory = $assetPipelineFactory;
        $this->io = $io;
    }

    public function create(): AbstractSite
    {
        $site = new BlogSite($this->blogContentFactory, $this->io);
        //$site->setAssetPipeline($this->assetPipelineFactory->create());
        return $site;
    }
}
