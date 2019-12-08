<?php


namespace nyansapow\sites;


use nyansapow\text\HtmlRenderer;
use nyansapow\text\TemplateEngine;

class BlogContentFactory
{
    private $htmlRenderer;
    private $frontMatterReader;
    private $templateEngine;

    public function __construct(TemplateEngine $templateEngine, HtmlRenderer $htmlRenderer, FrontMatterReader $frontMatterReader)
    {
        $this->htmlRenderer = $htmlRenderer;
        $this->frontMatterReader = $frontMatterReader;
        $this->templateEngine = $templateEngine;
    }

    public function createPost($source, $destination, $data): BlogPostContent
    {
        return new BlogPostContent($this->templateEngine, $this->htmlRenderer, $this->frontMatterReader, $source, $destination, $data);
    }

    public function createListing($posts, $destination, $data, $title) : BlogListingContent
    {
        $listing = new BlogListingContent($this->templateEngine, $posts, $destination, $data);
        $listing->setTitle($title);
        return $listing;
    }
}
