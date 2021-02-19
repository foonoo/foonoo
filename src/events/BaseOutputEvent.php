<?php


namespace foonoo\events;


use foonoo\content\Content;
use foonoo\sites\AbstractSite;

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

    public abstract function getOutput() : string;
}