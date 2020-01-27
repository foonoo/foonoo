<?php


namespace nyansapow\content;


use nyansapow\sites\FrontMatterReader;
use nyansapow\text\TextConverter;
use nyansapow\text\TemplateEngine;

class BlogContentFactory
{
    private $htmlRenderer;
    private $frontMatterReader;
    private $templateEngine;

    public function __construct(TemplateEngine $templateEngine, TextConverter $htmlRenderer, FrontMatterReader $frontMatterReader)
    {
        $this->htmlRenderer = $htmlRenderer;
        $this->frontMatterReader = $frontMatterReader;
        $this->templateEngine = $templateEngine;
    }

    public function createPost(string $source, string $destination): BlogPostContent
    {
        $content = new BlogPostContent($this->templateEngine, $this->htmlRenderer, $this->frontMatterReader, $source, $destination);
        //$content->setSite($site);
        return $content;
    }

    public function createPage($source, $destination) : BlogPageContent
    {
        //$page = new BlogPageContent($this->templateEngine, $this->htmlRenderer, $this->frontMatterReader, $source, $destination);
        return new BlogPageContent($this->htmlRenderer, $this->frontMatterReader, $source, $destination);
    }

    public function createListing($posts, $destination, $data, $title) : BlogListingContent
    {
        $listing = new BlogListingContent($this->templateEngine, $posts, $destination, $data);
        $listing->setTitle($title);
        return $listing;
    }
}
