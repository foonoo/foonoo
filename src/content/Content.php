<?php


namespace nyansapow\content;


abstract class Content
{
    protected $sitePath;
    protected $destination;

    abstract public function getMetaData(): array;
    abstract public function render(): string;

    public function setSitePath($sitePath)
    {
        $this->sitePath = $sitePath;
        return $this;
    }

    public function getFullDestination()
    {
        return $this->sitePath . "/" . $this->getDestination();
    }

    public function getDestination()
    {
        return $this->destination;
    }
}