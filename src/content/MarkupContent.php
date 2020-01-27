<?php


namespace nyansapow\content;

use nyansapow\content\Content;
use nyansapow\sites\AbstractSite;
use nyansapow\sites\ExtensionAdjuster;
use nyansapow\sites\FrontMatterReader;
use nyansapow\text\TextConverter;

/**
 * Class MarkupContent
 *
 * @package nyansapow\sites
 */
class MarkupContent extends Content implements PreprocessableInterface
{
    use ExtensionAdjuster;

    private $body;
    private $frontMatter;
    private $firstLineOfBody = 0;
    private $htmlRenderer;
    private $frontMatterReader;
    private $rendered;

    protected $document;

    public function __construct(TextConverter $htmlRenderer, FrontMatterReader $frontMatterReader, string $document, string $destination)
    {
        $this->document = $document;
        $this->htmlRenderer = $htmlRenderer;
        $this->destination = $this->adjustFileExtension($destination, 'html');
        $this->frontMatterReader = $frontMatterReader;
    }

//    public function setSite(AbstractSite $site) : void
//    {
//        $this->site = $site;
//    }

    protected function getFrontMatter() : array
    {
        if(!$this->frontMatter) {
            $this->frontMatter = $this->frontMatterReader->read($this->document, $this->firstLineOfBody);
        }
        return $this->frontMatter;
    }

    public function getBody() : string
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

    public function render() : string
    {
        if(!$this->rendered) {
            $this->getFrontMatter();
            $fromFormat = pathinfo($this->document, PATHINFO_EXTENSION);
            $toFormat = pathinfo($this->destination, PATHINFO_EXTENSION);
            $this->rendered = $this->htmlRenderer->convert($this->getBody(), $fromFormat, $toFormat);
        }
        return $this->rendered;
    }

    public function getMetaData(): array
    {
        return $this->getFrontMatter();
    }
}
