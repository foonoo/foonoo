<?php

namespace foonoo\content;

use ntentan\honam\exceptions\FactoryException;
use ntentan\honam\exceptions\TemplateEngineNotFoundException;
use ntentan\honam\exceptions\TemplateResolutionException;
use ntentan\honam\TemplateRenderer;
use foonoo\sites\FrontMatterReader;
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
    private $id;
    
    /**
     * @var TagParser
     */
    private $parser;
    
    /**
     * @var FrontMatterReader
     */
    private $frontMatterParser;

    public function __construct(TemplateRenderer $templates, TagParser $parser, FrontMatterReader $frontMatterParser, string $source, string $destination)
    {
        $this->templates = $templates;
        $this->source = $source;
        $this->destination = $destination;
        $this->parser = $parser;
        $this->frontMatterParser = $frontMatterParser;
        $this->id = uniqid("tc_", true);
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
        $extension = pathinfo($this->source, PATHINFO_EXTENSION);
        list($this->metaData, $body) = $this->frontMatterParser->read($this->source);
        return $this->templates->render(
                $this->parser->parse($body),
                $this->data, true, $extension
            );
    }

    public function setMetaData(array $metaData): void
    {
        $this->metaData = $metaData;
    }
    
    public function setFirstLineOfBody(int $firstLineOfBody): void
    {
        $this->firstLineOfBody = $firstLineOfBody;
    }

    public function setData($data): void
    {
        $this->data = $data;
    }

    public function getMetaData(): array
    {
        return $this->metaData;
    }

    public function getLayoutData()
    {
        return ['page_type' => 'template'];
    }

    public function getID(): string
    {
        return $this->id;
    }

}
