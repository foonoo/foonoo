<?php


namespace foonoo\events;

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

    public Content $content {
        get {
            return $this->content;
        }
    }
    public AbstractSite $site {
        get {
            return $this->site;
        }
    }

    protected bool $domPossiblyModified = false;

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

    public abstract function getOutput() : string;
}