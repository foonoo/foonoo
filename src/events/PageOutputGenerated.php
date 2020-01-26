<?php


namespace nyansapow\events;


use nyansapow\content\Content;
use nyansapow\sites\AbstractSite;

class PageOutputGenerated //extends Event
{
    private $output;
    private $page;
    private $site;

    public function __construct(string $output, Content $page, AbstractSite $site)
    {
        $this->output = $output;
        $this->page = $page;
        $this->site = $site;
    }

    public function setOutput(string $output): void
    {
        $this->output = $output;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getSite(): AbstractSite
    {
        return $this->site;
    }

    public function getPage(): Content
    {
        return $this->page;
    }
}