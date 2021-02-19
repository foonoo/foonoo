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
class ContentLayoutApplied extends BaseOutputEvent
{
    /**
     * If content has a DOM, you can use this to get it.
     *
     * @return \DOMDocument
     */
    public function getDOM(): \DOMDocument
    {
        // Create a DOM tree for objects that are possibly themed
        if (!$this->dom && is_a($this->content, ThemableInterface::class)) {
            $this->dom = new \DOMDocument();
            $this->dom->loadHTML($this->output, LIBXML_HTML_NODEFDTD);
        }
        $this->domPossiblyModified = true;
        return $this->dom;
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
}