<?php


namespace foonoo\content;

use ntentan\honam\exceptions\FactoryException;
use ntentan\honam\exceptions\TemplateEngineNotFoundException;
use ntentan\honam\exceptions\TemplateResolutionException;
use ntentan\honam\TemplateRenderer;

class TemplateContent extends Content implements DataRenderer
{
    private $source;
    private $templates;
    private $data = [];
    private $metaData = [];

    public function __construct(TemplateRenderer $templates, $source, $destination)
    {
        $this->templates = $templates;
        $this->source = $source;
        $this->destination = $destination; //$this->adjustFileExtension($destination, 'html');
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
        return $this->metaData;
    }

    public function setLayout($layout)
    {
        $this->metaData['layout'] = $layout;
    }
}