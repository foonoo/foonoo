<?php

namespace foonoo\events;


use foonoo\content\Content;
use foonoo\content\ThemableInterface;
use foonoo\sites\AbstractSite;

/**
 * This event is triggered after the output of any content is generated and ready to be written.
 *
 * @package foonoo\events
 */
class PageOutputGenerated
{
    private $output;
    private $page;
    private $site;
    private $dom;
    private $domPossiblyModified;

    public function __construct(string $output, Content $page, AbstractSite $site)
    {
        $this->page = $page;
        $this->site = $site;
        $this->setOutput($output);
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
     * Get the output that was generated.
     *
     * @return string
     */
    public function getOutput(): string
    {
        if($this->dom && $this->domPossiblyModified) {
            $this->output = $this->dom->saveHTML();
            $this->domPossiblyModified = false;
        }
        return $this->output;
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

    /**
     * If content has a DOM, you can use this to get it.
     *
     * @return \DOMDocument
     */
    public function getDOM() : \DOMDocument
    {
        // Create a DOM tree for objects that are possibly themed
        if(!$this->dom && is_a($this->page, ThemableInterface::class)) {
            $this->dom = new \DOMDocument();
            @$this->dom->loadHTML($this->output);
        }
        $this->domPossiblyModified = true;
        return $this->dom;
    }

    public function getPage(): Content
    {
        return $this->page;
    }
}