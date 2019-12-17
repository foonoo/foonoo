<?php

namespace nyansapow\text;

use DOMDocument;

class HtmlRenderer
{
    private $info = null;
    private $parser;
    private $dom;

    public function __construct(TagParser $parser, DOMDocument $dom)
    {
        $this->parser = $parser;
        $this->dom = $dom;
    }

    private function getInfo()
    {
        if ($this->info === null) {
            $this->info = finfo_open(FILEINFO_MIME);
        }
        return $this->info;
    }

    /**
     * Render text
     *
     * @param $content
     * @param $format
     * @param array $options
     * @return string
     */
    public function render($content, $site=null, $page=null, $data = [])
    {
        if($content == "") {
            return "";
        }
        $parsed = $this->parser->parse($content, $site, $page);
        return $this->parseMarkdown($parsed);
        //@$this->dom->loadHTML($markedup);
        //return $this->parser->postParse($markedup);
    }

    private function parseMarkdown($content)
    {
        $parsedown = new \Parsedown();
        return $parsedown->text($content);
    }
}
