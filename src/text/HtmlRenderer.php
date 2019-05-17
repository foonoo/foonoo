<?php

namespace nyansapow\text;

use DOMDocument;
use nyansapow\TocRequestedException;

class HtmlRenderer
{
    private $title;
    private $info = null;
    private $parser;
    private $dom;

    public function __construct(Parser $parser, DOMDocument $dom)
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
    public function render($content, $format, $options = [])
    {
        if($content == "") return "";
        libxml_use_internal_errors(true);
        $preParsed = $this->parser->preParse($content);
        $markedup = $this->parse($preParsed, $format, $options['data'] ?? []);
        $this->dom->loadHTML($markedup);
        if($this->dom->getElementsByTagName('h1')->item(0)) {
            $this->title = $this->dom->getElementsByTagName('h1')->item(0)->textContent;
        }
        $this->parser->domCreated($this->dom);
        $body = $this->dom->getElementsByTagName('body');

        try {
            // Force the parsing of a TOC
            if (isset($options['toc'])) {
                throw new TocRequestedException();
            }

            // Could throw a TocRequested exception to force generation of table of contents
            $parsed = $this->parser->postParse($markedup);
        } catch (TocRequestedException $e) {
            $parsed = $this->parser->postParse(
                str_replace(['<body>', '</body>'],'', $this->dom->saveHTML($body->item(0))), false
            );
        }

        return $parsed;
    }

    public function getTitle()
    {
        return $this->title;
    }

    private function parse($content, $format, $data)
    {
        if ($format == 'md') {
            return $this->parseMarkdown($content);
        } elseif (TemplateEngine::canRender("dummy.$format")) { // check rendereability of a dummy file with format
            return TemplateEngine::renderString($content, $format, $data);
        } else {
            return $content;
        }
    }

    private function parseMarkdown($content)
    {
        $parsedown = new \Parsedown();
        return $parsedown->text($content);
    }

    public function isFileRenderable($file)
    {
        $mimeType = finfo_file($this->getInfo(), $file);
        return (substr($mimeType, 0, 4) === 'text' && substr($file, -2) == 'md') || TemplateEngine::canRender($file);
    }

    public function getTableOfContents()
    {
        return Parser::getTableOfContents();
    }

    public function setTypeIndex($typeIndex)
    {
        Parser::setTypeIndex($typeIndex);
    }

    public function setPages($pages)
    {
        Parser::setPages($pages);
    }
}
