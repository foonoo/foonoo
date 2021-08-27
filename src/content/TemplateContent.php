<?php


namespace foonoo\content;

use ntentan\honam\exceptions\FactoryException;
use ntentan\honam\exceptions\TemplateEngineNotFoundException;
use ntentan\honam\exceptions\TemplateResolutionException;
use ntentan\honam\TemplateRenderer;
use foonoo\text\TagParser;

/**
 * This class represents content generated from rendered templates.
 * 
 * @author Ekow Abaka
 *
 */
class TemplateContent extends Content implements DataRenderer, ThemableInterface
{
    private $source;
    private $templates;
    private $data = [];
    private $metaData = [];
    private $parser;

    public function __construct(TemplateRenderer $templates, TagParser $parser, $source, $destination)
    {
        $this->templates = $templates;
        $this->source = $source;
        $this->destination = $destination;
        $this->parser = $parser;
    }

    /**
     * Generate the output by rendering the content with any data.
     * 
     * @return string
     * @throws FactoryException
     * @throws TemplateEngineNotFoundException
     * @throws TemplateResolutionException
     */
    public function render(): string
    {
        $extension = substr($this->source, strpos($this->source, ".") + 1);
        return $this->templates->render(
                $this->parser->parse(file_get_contents($this->source)), 
                $this->data, true, $extension
            );
    }

    public function setData($data): void
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

    public function getLayoutData()
    {
        return ['page_type' => 'template'];
    }
}