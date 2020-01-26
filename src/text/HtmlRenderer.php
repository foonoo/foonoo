<?php

namespace nyansapow\text;

use nyansapow\sites\AbstractSite;
use nyansapow\content\Content;

class HtmlRenderer
{
    private $parser;

    /**
     * HtmlRenderer constructor.
     * @param TagParser $parser
     */
    public function __construct(TagParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Render text
     *
     * @param string $content
     * @param Content $page
     * @return string
     */
    public function render(string $content, Content $page=null)
    {
        if($content == "") {
            return "";
        }
        $parsed = $this->parser->parse($content, $page);
        return $this->parseMarkdown($parsed);
    }

    private function parseMarkdown($content)
    {
        $parsedown = new \Parsedown();
        return $parsedown->text($content);
    }
}
