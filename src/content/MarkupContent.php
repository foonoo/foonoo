<?php


namespace nyansapow\content;

use nyansapow\content\Content;
use nyansapow\sites\AbstractSite;
use nyansapow\sites\ExtensionAdjuster;
use nyansapow\sites\FrontMatterReader;
use nyansapow\text\HtmlRenderer;

/**
 * Class MarkupContent
 *
 * @package nyansapow\sites
 */
class MarkupContent extends Content
{
    use ExtensionAdjuster;

    private $body;
    private $document;
    private $frontMatter;
    private $firstLineOfBody = 0;
    private $htmlRenderer;
    private $frontMatterReader;
    private $rendered;

//    /**
//     * @var AbstractSite
//     */
    //protected $site;

    public function __construct(HtmlRenderer $htmlRenderer, FrontMatterReader $frontMatterReader, string $document, string $destination)
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

    protected function getBody() : string
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
            $this->rendered = $this->htmlRenderer->render($this->getBody(), $this);
        }
        return $this->rendered;
    }

    public function getMetaData(): array
    {
        return $this->getFrontMatter();
    }
}
