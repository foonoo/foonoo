<?php


namespace foonoo\events;


use foonoo\content\Content;
use foonoo\content\ThemableInterface;
use foonoo\sites\AbstractSite;

/**
 * Class BaseOutputEvent
 * @package foonoo\events
 */
class BaseOutputEvent
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
        if ($this->dom && $this->domPossiblyModified) {
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
    public function getDOM(): \DOMNode
    {
        // Create a DOM tree for objects that are possibly themed
        if (!$this->dom && is_a($this->content, ThemableInterface::class)) {
            $this->dom = new \DOMDocument();
            $this->dom->loadHTML($this->output);
        }
        $this->domPossiblyModified = true;
        return $this->dom;
    }

    public function getContent(): Content
    {
        return $this->content;
    }
}