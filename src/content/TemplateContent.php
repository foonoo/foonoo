<?php


namespace nyansapow\content;

use ntentan\honam\exceptions\FactoryException;
use ntentan\honam\exceptions\TemplateEngineNotFoundException;
use ntentan\honam\exceptions\TemplateResolutionException;
use ntentan\honam\TemplateRenderer;
use nyansapow\sites\AbstractSite;
use nyansapow\sites\ExtensionAdjuster;

class TemplateContent extends Content implements DataRendererInterface
{
    use ExtensionAdjuster;

    private $source;
    private $templates;
    private $data = [];

    public function __construct(TemplateRenderer $templates, $source, $destination)
    {
        $this->templates = $templates;
        $this->source = $source;
        $this->destination = $this->adjustFileExtension($destination, 'html');
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

    public function getMetaData(): array
    {
        return [];
    }
}