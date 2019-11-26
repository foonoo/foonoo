<?php


namespace nyansapow\sites;


class CopiedContent implements ContentInterface
{
    private $source;
    private $destination;

    public function __construct(string $source, string $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
    }

    public function render(): string
    {
        return file_get_contents($this->source);
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getMetaData(): array
    {
        return ['layout' => false];
    }
}