<?php


namespace nyansapow\sites;

use ntentan\honam\TemplateRenderer;

class TemplateContent implements ContentInterface
{
    private $source;
    private $destination;
    private $templates;
    private $data;

    public function __construct(TemplateRenderer $templates, AbstractSite $site, $source, $destination)
    {
        $this->templates = $templates;
        $this->source = $source;
        $extension = pathinfo($this->source, PATHINFO_EXTENSION);
        $this->destination = $extension ? substr($destination, 0, -strlen($extension)) . "html" : $destination;
        $this->data = $site->getTemplateData($site->getDestinationPath($destination));
    }

    /**
     * @return string
     * @throws \ntentan\honam\exceptions\FactoryException
     * @throws \ntentan\honam\exceptions\TemplateEngineNotFoundException
     * @throws \ntentan\honam\exceptions\TemplateResolutionException
     */
    public function render(): string
    {
        return $this->templates->render($this->source, $this->data);
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getMetaData(): array
    {
        return [];
    }
}