<?php


namespace foonoo\events;


use foonoo\content\Content;

class ContentGenerationStarted
{
    private $content;

    public function __construct(Content $content)
    {
        $this->content = $content;
    }

    public function getContent() : Content
    {
        return $this->content;
    }
}