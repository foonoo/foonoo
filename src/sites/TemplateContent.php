<?php


namespace nyansapow\sites;

use ntentan\honam\TemplateRenderer;

class TemplateContent implements ContentInterface
{
    private $source;
    private $destination;
    private $templates;
    private $data;

    public function __construct(TemplateRenderer $templates, $source, $destination, $data)
    {
        $this->templates = $templates;
        $this->source = $source;
        $extension = pathinfo($this->source, PATHINFO_EXTENSION);
        $this->destination = substr($destination, 0, -strlen($extension)) . "html";
        $this->data = $data;
    }

    public function render(): string
    {
        return $this->templates->render($this->source, $this->data);
    }

    public function getDestination(): string
    {
        return $this->destination;
    }
}