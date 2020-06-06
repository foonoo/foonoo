<?php

namespace nyansapow\events;


use nyansapow\content\Content;
use nyansapow\content\ThemableInterface;
use nyansapow\sites\AbstractSite;

class PageOutputGenerated
{
    private $output;
    private $page;
    private $site;
    private $dom;
    private $domPossiblyModified;

    public function __construct(string $output, Content $page, AbstractSite $site)
    {
        $this->page = $page;
        $this->site = $site;
        $this->setOutput($output);
    }

    public function setOutput(string $output): void
    {
        $this->output = $output;
    }

    public function getOutput(): string
    {
        if($this->dom && $this->domPossiblyModified) {
            $this->output = $this->dom->saveHTML();
            $this->domPossiblyModified = false;
        }
        return $this->output;
    }

    public function getSite(): AbstractSite
    {
        return $this->site;
    }

    public function getDOM() : \DOMDocument
    {
        // Create a DOM tree for objects that are possibly themed
        if(!$this->dom && is_a($this->page, ThemableInterface::class)) {
            $this->dom = new \DOMDocument();
            @$this->dom->loadHTML($this->output);
        }
        $this->domPossiblyModified = true;
        return $this->dom;
    }

    public function getPage(): Content
    {
        return $this->page;
    }
}