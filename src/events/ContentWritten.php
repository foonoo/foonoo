<?php


namespace foonoo\events;


use foonoo\content\Content;

class ContentWritten
{
    private $content;
    private $destinationPath;

    public function __construct(Content $content, string $destinationPath)
    {
        $this->content = $content;
        $this->destinationPath = $destinationPath;
    }

    public function getContent() : Content
    {
        return $this->content;
    }

    public function getDestinationPath() : string
    {
        return $this->destinationPath;
    }
}