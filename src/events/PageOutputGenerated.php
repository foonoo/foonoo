<?php


namespace nyansapow\events;


use nyansapow\content\ContentInterface;
use nyansapow\sites\AbstractSite;

class PageOutputGenerated extends Event
{
    private $output;
    private $page;
    private $site;

    public function __construct(string $output, ContentInterface $page, AbstractSite $site)
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

    public function getPage(): ContentInterface
    {
        return $this->page;
    }
}