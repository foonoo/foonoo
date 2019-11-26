<?php


namespace nyansapow\sites;


use nyansapow\text\HtmlRenderer;

class BlogContent extends MarkupContent
{
    private $params;
    private $metaData;
    private $next;
    private $previous;

    public function __construct(HtmlRenderer $htmlRenderer, $document, $destination, $params)
    {
        parent::__construct($htmlRenderer, $document, $destination);
        $this->params = $params;
    }

    public function getPostData()
    {
        if(!$this->metaData) {
            $splitPost = $this->splitPost();
            $frontMatter = $this->getFrontMatter();
            $this->metaData = [
                'body_text' => $splitPost['post'],
                'title' => $frontMatter['title'] ?? ucfirst(str_replace("-", " ", $this->params['title'])),
                'date' => date("jS F Y", strtotime("{$this->params['year']}-{$this->params['month']}-{$this->params['day']}")),
                'preview_text' => $splitPost['preview'],
                'continuation' => $splitPost['continuation'],
                'front_matter' => $frontMatter,
                'more_link' => $splitPost['more_link'],
            ];
        }
    }

    public function render(): string
    {
        return parent::render();
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

    public function setNext(BlogContent $next)
    {
        $this->next = $next;
    }

    public function setPrevious(BlogContent $previous)
    {
        $this->previous = $previous;
    }
}
