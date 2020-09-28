<?php


namespace foonoo\events;


use foonoo\content\Content;

class ContentWriteStarted
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