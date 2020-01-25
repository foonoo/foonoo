<?php


namespace nyansapow\content;

use ntentan\honam\exceptions\FactoryException;
use ntentan\honam\exceptions\TemplateEngineNotFoundException;
use ntentan\honam\exceptions\TemplateResolutionException;
use ntentan\honam\TemplateRenderer;
use nyansapow\sites\AbstractSite;
use nyansapow\sites\ExtensionAdjuster;

class TemplateContent implements ContentInterface, DataRendererInterface
{
    use ExtensionAdjuster;

    private $source;
    private $destination;
    private $templates;
    private $data = [];
    //private $site;

    public function __construct(TemplateRenderer $templates, $source, $destination)
    {
        $this->templates = $templates;
        $this->source = $source;
        $this->destination = $this->adjustFileExtension($destination, 'html');
        //$this->site = $site;
    }

    /**
     * @return string
     * @throws FactoryException
     * @throws TemplateEngineNotFoundException
     * @throws TemplateResolutionException
     */
    public function render(): string
    {
        return $this->templates->render(
            $this->source,
            $this->data //?? $this->site->getTemplateData($this->site->getDestinationPath($this->destination))
        );
    }

    public function setData($data) : void
    {
        $this->data = $data;
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