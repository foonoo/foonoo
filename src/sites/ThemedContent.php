<?php


namespace nyansapow\sites;


class ThemedContent implements ContentInterface
{
    private $wrappedContent;

    public function __construct(ContentInterface $wrappedContent)
    {
        $this->wrappedContent = $wrappedContent;
    }

    public function render(): string
    {

    }

    public function getDestination(): string
    {
        // TODO: Implement getDestination() method.
    }
}