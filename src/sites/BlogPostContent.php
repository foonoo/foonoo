<?php


namespace nyansapow\sites;


use nyansapow\text\HtmlRenderer;
use nyansapow\text\TemplateEngine;

class BlogPostContent extends MarkupContent
{
    private $params;
    private $metaData;
    private $next;
    private $previous;
    private $htmlRenderer;
    private $templateEngine;

    public function __construct(TemplateEngine $templateEngine, HtmlRenderer $htmlRenderer, FrontMatterReader $frontMatterReader, $document, $destination, $params)
    {
        parent::__construct($htmlRenderer, $frontMatterReader, $document, $destination);
        $this->htmlRenderer = $htmlRenderer;
        $this->params = $params;
        $this->templateEngine = $templateEngine;
    }

    public function getMetaData() : array
    {
        if(!$this->metaData) {
            $frontMatter = $this->getFrontMatter();
//            $splitPost = $this->splitPost();
            $this->metaData = [
//                'body_text' => $splitPost['post'],
                'title' => $frontMatter['title'] ?? ucfirst(str_replace("-", " ", $this->params['title'])),
                'date' => date("jS F Y", strtotime("{$this->params['year']}-{$this->params['month']}-{$this->params['day']}")),
//                'preview_text' => $splitPost['preview'],
//                'continuation' => $splitPost['continuation'],
                'front_matter' => $frontMatter,
//                'more_link' => $splitPost['more_link'],
                'path' => "{$this->params['year']}/{$this->params['month']}/{$this->params['day']}/{$this->params['title']}.html",
//                'preview' => $this->htmlRenderer->render($splitPost['preview'])
            ];
        }
        return $this->metaData;
    }

    public function render(): string
    {
        return $this->templateEngine->render('post', array_merge(['body' => parent::render()],  $this->getMetaData()));
    }

    public function getPreview() : string
    {
        $splitPost = $this->splitPost();
        return $this->htmlRenderer->render($splitPost['preview']);
    }

    private function splitPost()
    {
        $post = $this->getBody();
        $previewRead = false;
        $lines = explode("\n", $post);
        $preview = '';
        $body = '';
        $continuation = '';
        $moreLink = false;

        foreach ($lines as $line) {
            if (preg_match("/(?<preview>.*)(?<tag>\<\!\-\-\s*more\s*\-\-\>)(?<line>.*)/i", $line, $matches)) {
                $preview .= "{$matches['preview']}\n";
                $body .= "{$matches['preview']} {$matches['line']}\n";
                $previewRead = true;
                $moreLink = true;
                $continuation .= "{$matches['line']}\n";
                continue;
            }
            if (!$previewRead) {
                $preview .= "$line\n";
            } else {
                $continuation .= "$line\n";
            }
            $body .= "$line\n";
        }

        return ['post' => $body, 'preview' => $preview, 'more_link' => $moreLink, "continuation" => $continuation];
    }

    public function setNext(BlogPostContent $next)
    {
        $this->next = $next;
    }

    public function setPrevious(BlogPostContent $previous)
    {
        $this->previous = $previous;
    }
}
