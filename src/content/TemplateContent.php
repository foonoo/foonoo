<?php


namespace nyansapow\content;

use ntentan\honam\TemplateRenderer;
use nyansapow\content\ContentInterface;
use nyansapow\sites\AbstractSite;
use nyansapow\sites\ExtensionAdjuster;

class TemplateContent implements ContentInterface
{
    use ExtensionAdjuster;

    private $source;
    private $destination;
    private $templates;
    private $data;

    public function __construct(TemplateRenderer $templates, AbstractSite $site, $source, $destination)
    {
        $this->templates = $templates;
        $this->source = $source;
        $this->destination = $this->adjustFileExtension($destination, 'html');
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