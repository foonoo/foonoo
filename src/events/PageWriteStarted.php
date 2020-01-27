<?php


namespace nyansapow\events;


use nyansapow\content\Content;

class PageWriteStarted
{
    private $page;

    public function __construct($page)
    {
        $this->page = $page;
    }

    public function getPage() : Content
    {
        return $this->page;
    }
}