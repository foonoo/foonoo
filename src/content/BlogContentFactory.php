<?php


namespace nyansapow\content;


use nyansapow\sites\AbstractSite;
use nyansapow\content\BlogListingContent;
use nyansapow\content\BlogPageContent;
use nyansapow\content\BlogPostContent;
use nyansapow\sites\FrontMatterReader;
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

    public function createPost(AbstractSite $site, string $source, string $destination): BlogPostContent
    {
        $content = new BlogPostContent($this->templateEngine, $this->htmlRenderer, $this->frontMatterReader, $source, $destination);
        $content->setSite($site);
        return $content;
    }

    public function createPage(AbstractSite $site, $source, $destination) : BlogPageContent
    {
        $page = new BlogPageContent($this->templateEngine, $this->htmlRenderer, $this->frontMatterReader, $source, $destination);
        $page->setSite($site);
        return $page;
    }

    public function createListing($posts, $destination, $data, $title) : BlogListingContent
    {
        $listing = new BlogListingContent($this->templateEngine, $posts, $destination, $data);
        $listing->setTitle($title);
        return $listing;
    }
}
