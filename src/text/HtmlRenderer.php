<?php

namespace nyansapow\text;

use DOMDocument;
use ntentan\honam\TemplateRenderer;
use nyansapow\TocRequestedException;

class HtmlRenderer
{
    private $title;
    private $info = null;
    private $parser;
    private $dom;
    /**
     * @var TemplateRenderer
     */
    private $templateRenderer;

    public function __construct(TagParser $parser, TemplateRenderer $templateRenderer, DOMDocument $dom)
    {
        $this->parser = $parser;
        $this->templateRenderer = $templateRenderer;
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
    public function render($content, $format, $data)
    {
        if($content == "") {
            return "";
        }

        libxml_use_internal_errors(true);
        $preParsed = $this->parser->preParse($content);
        $markedup = $this->parse($preParsed, $format, $data);
        $this->dom->loadHTML($markedup);

        if($this->dom->getElementsByTagName('h1')->item(0)) {
            $this->title = $this->dom->getElementsByTagName('h1')->item(0)->textContent;
        }

        return $this->parser->postParse($markedup);
    }

    public function getTitle()
    {
        return $this->title;
    }

    private function parse($content, $format, $data)
    {
        if ($format == 'md') {
            return $this->parseMarkdown($content);
        } elseif ($this->templateRenderer->canRender("dummy.$format")) { // check rendereability of a dummy file with format
            return $this->templateRenderer->render($content, $data, true, $format);
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
        return (substr($mimeType, 0, 4) === 'text' && substr($file, -2) == 'md') || $this->templateRenderer->canRender($file);
    }
}
