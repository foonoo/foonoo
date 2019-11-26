<?php


namespace nyansapow\sites;

use nyansapow\text\HtmlRenderer;
use Symfony\Component\Yaml\Parser as YamlParser;

class MarkupContent implements ContentInterface
{
    private $body;
    private $document;
    private $destination;
    private $frontMatter;
    private $firstLineOfBody = 0;
    private $htmlRenderer;
    private $yamlParser;
    private $frontMatterReader;

    public function __construct(HtmlRenderer $htmlRenderer, FrontMatterReader $frontMatterReader, $document, $destination)
    {
        $this->document = $document;
        $this->htmlRenderer = $htmlRenderer;
        $this->destination = $destination;
        $this->frontMatterReader = $frontMatterReader;
    }

    protected function getFrontMatter()
    {
        if(!$this->frontMatter) {
            $this->frontMatter = $this->frontMatterReader->read($this->document, $this->firstLineOfBody);
        }
        return $this->frontMatter;
    }

    protected function getBody()
    {
        if(!$this->body) {
            $file = new \SplFileObject($this->document);
            $file->seek($this->firstLineOfBody);

            while(!$file->eof()) {
                $this->body .= $file->fgets();
            }
        }
        return $this->body;
    }

    public function getDestination() : string
    {
        return $this->destination;
    }

    public function render() : string
    {
        $this->getFrontMatter();
        return $this->htmlRenderer->render($this->getBody(), []);
    }

    public function getMetaData(): array
    {
        return $this->getFrontMatter();
    }
}
