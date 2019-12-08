<?php


namespace nyansapow\sites;


use nyansapow\text\TemplateEngine;

class BlogListingContent implements ContentInterface
{
    private $posts;
    private $templateEngine;
    private $destination;

    public function __construct(TemplateEngine $templateRenderer, array $posts, $destination)
    {
        $this->posts = $posts;
        $this->templateEngine = $templateRenderer;
        $this->destination = $destination;
    }

    public function getMetaData(): array
    {
        return [
            'posts' => $this->posts
        ];
    }

    public function render(): string
    {
        $posts = array_map(function (BlogPostContent $post) {
            $templateVars = $post->getMetaData();
            $templateVars['preview'] = $post->getPreview();
            $templateVars['previews_only'] = true;
            return $templateVars;
        }, $this->posts);

        return $this->templateEngine->render('listing', ['posts' => $posts]);
    }

    public function getDestination(): string
    {
        return $this->destination;
    }
}