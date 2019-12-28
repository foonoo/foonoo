<?php


namespace nyansapow\sites;


use nyansapow\content\BlogContentFactory;

class BlogSiteFactory implements SiteFactoryInterface
{
    private $blogContentFactory;

    public function __construct(BlogContentFactory $blogContentFactory)
    {
        $this->blogContentFactory = $blogContentFactory;
    }

    public function create(array $metadata, string $path): AbstractSite
    {
        return new BlogSite($this->blogContentFactory);
    }
}