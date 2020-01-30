<?php


namespace nyansapow\events;


use nyansapow\content\Content;

class PageWriteStarted
{
    private $content;

    public function __construct(Content $page)
    {
        $this->content = $page;
    }

    public function getContent() : Content
    {
        return $this->content;
    }
}