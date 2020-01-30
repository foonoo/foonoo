<?php


namespace nyansapow\text;


class MarkdownConverter implements ConverterInterface
{
    private $parsedown;

    public function __construct(\Parsedown $parsedown)
    {
        $this->parsedown = $parsedown;
    }

    public function convert($input): string
    {
        return $this->parsedown->text($input);
    }
}

