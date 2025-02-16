<?php


namespace foonoo\events;

use Dom\HTMLDocument;
use foonoo\content\Content;
use foonoo\content\ThemableInterface;
use foonoo\sites\AbstractSite;
use Dom\Node;

/**
 * Class BaseOutputEvent
 * @package foonoo\events
 */
abstract class BaseOutputEvent
{
    /**
     * The final output of concert to the event.
     * @var string
     */
    protected string $output;

    private Content $content;

    private AbstractSite $site;

    private ?HTMLDocument $dom = null;

    private bool $domAccessed = false;

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
        $this->dom = null;
        $this->domAccessed = false;
    }

    public function getDOM(): ?Node
    {
        // Create a DOM tree for objects that are possibly themed
        if ($this->dom === null && $this->hasDOM()) {
            $this->dom = HTMLDocument::createFromString($this->output, LIBXML_NOERROR);
            $this->domAccessed = true;
        }
        return $this->dom;
    }

    public abstract function getOutput(): string;

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getSite(): AbstractSite
    {
        return $this->site;
    }

    public function isDomAccessed(): bool
    {
        return $this->domAccessed;
    }

    public function resetDom(): void
    {
        $this->dom = null;
        $this->domAccessed = false;
    }
}