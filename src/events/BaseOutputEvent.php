<?php


namespace foonoo\events;

use foonoo\content\Content;
use foonoo\content\ThemableInterface;
use foonoo\sites\AbstractSite;
use foonoo\exceptions\ContentGenerationException;

/**
 * Class BaseOutputEvent
 * @package foonoo\events
 */
abstract class BaseOutputEvent
{
    protected $output;
    protected $content;
    protected $site;

    /**
     * @var \DOMDocument
     */
    protected $dom;
    protected $domPossiblyModified;

    public function __construct(string $output, Content $content, AbstractSite $site)
    {
        $this->content = $content;
        $this->site = $site;
        $this->setOutput($output);
    }

    public function hasDOM(): bool
    {
        return is_a($this->content, ThemableInterface::class);
    }

    /**
     * Replace the output to be written.
     *
     * @param string $output
     */
    public function setOutput(string $output): void
    {
        $this->output = $output;
    }

    /**
     * Get the site for which this output was generated.
     *
     * @return AbstractSite
     */
    public function getSite(): AbstractSite
    {
        return $this->site;
    }

    public function getContent(): Content
    {
        return $this->content;
    }
    
//    public function getDOM(): \DOMDocument
//    {
//        $dom = $this->extractDom();                    
//        if($dom === null) {
//            throw new ContentGenerationException("Failed to extract DOM for {$this->content->getFullDestination()}");
//        }
//        return $dom;
//    }

    public abstract function getOutput() : string;
    
    public abstract function getDom(): ?\DOMDocument;
}