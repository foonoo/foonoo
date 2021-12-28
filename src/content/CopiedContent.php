<?php


namespace foonoo\content;


class CopiedContent extends Content
{
    private $source;
    private $id;

    public function __construct(string $source, string $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
        $this->id = uniqid("mc_", true);
    }

    public function render(): string
    {
        return file_get_contents($this->source);
    }

    public function getMetaData(): array
    {
        return ['layout' => false];
    }

    public function getID() : string
    {
        return $this->id;
    }
}