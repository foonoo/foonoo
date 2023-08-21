<?php

namespace foonoo\content;

use foonoo\sites\FrontMatterReader;
use foonoo\text\TextConverter;

/**
 * A class for content that's represented as Markdown files.
 */
class MarkupContent extends Content implements ThemableInterface
{
    private $body;
    private $frontMatter;
    private $textConverter;
    private $frontMatterReader;
    private $rendered;
    private $id;

    protected $document;

    public function __construct(TextConverter $textConverter, FrontMatterReader $frontMatterReader, string $document, string $destination)
    {
        $this->document = $document;
        $this->textConverter = $textConverter;
        $this->destination = $destination;
        $this->frontMatterReader = $frontMatterReader;
        $this->id = uniqid("mc_", true);
    }

    /**
     * Return the front matter from the markup.
     * 
     * @return array
     */
    protected function getFrontMatter() : array
    {
        if(!$this->frontMatter) {
            list($this->frontMatter, $this->body) = $this->frontMatterReader->read($this->document);
        }
        return $this->frontMatter;
    }

    /**
     * Return the rendered body of the Markedup string.
     * @return string
     */
    protected function getBody() : string
    {
        return $this->body;
    }

    public function render() : string
    {
        if(!$this->rendered) {
            $this->getFrontMatter();
            $fromFormat = pathinfo($this->document, PATHINFO_EXTENSION);
            $toFormat = pathinfo($this->destination, PATHINFO_EXTENSION);
            $this->rendered = $this->textConverter->convert($this->getBody(), $fromFormat, $toFormat);
        }
        return $this->rendered;
    }

    public function getMetaData(): array
    {
        return ['frontmatter' => $this->getFrontMatter()];
    }

    public function getLayoutData()
    {
        return [];
    }

    public function getID(): string
    {
        return $this->id;
    }

}
