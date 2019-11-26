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

    public function __construct(HtmlRenderer $htmlRenderer, $document, $destination)
    {
        $this->document = $document;
        $this->htmlRenderer = $htmlRenderer;
        $this->destination = $destination;
        $this->yamlParser = new YamlParser();
    }

    protected function getFrontMatter()
    {
        if(!$this->frontMatter) {
            $this->readFrontMatter();
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
        $this->readFrontMatter();
        return $this->htmlRenderer->render($this->getBody(), []);
    }

    private function readFrontMatter() : void
    {
        $file = fopen($this->document, 'r');

        while (!feof($file)) {
            $line = fgets($file);
            if (trim($line) == "---") {
                $this->frontMatter = $this->extractAndDecodeFrontMatter($file);
                break;
            } else if (trim($line) != "") {
                $this->firstLineOfBody = 0;
                break;
            } else {
                $this->firstLineOfBody += 1;
            }
        }
    }

    private function extractAndDecodeFrontMatter($file) : array
    {
        $frontmatter = '';
        do {
            $line = fgets($file);
            $this->firstLineOfBody += 1;
            if (trim($line) == "---") break;
            $frontmatter .= $line;
        } while (!feof($file));

        return $this->yamlParser->parse($frontmatter);
    }

    public function getMetaData(): array
    {
        return $this->getFrontMatter();
    }
}
