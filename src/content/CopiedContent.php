<?php


namespace nyansapow\content;


use nyansapow\content\Content;

class CopiedContent extends Content
{
    private $source;

    public function __construct(string $source, string $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
    }

    public function render(): string
    {
        return file_get_contents($this->source);
    }

    public function getMetaData(): array
    {
        return ['layout' => false];
    }
}