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
class ContentOutputGenerated extends BaseOutputEvent
{
    public function getDOM(): \DOMNode
    {
        // Create a DOM tree for objects that are possibly themed
        if (!$this->dom && is_a($this->content, ThemableInterface::class)) {
            $this->dom = new \DOMDocument();
            $this->dom->loadHTML($this->output, LIBXML_HTML_NODEFDTD);
        }
        $this->domPossiblyModified = true;
        return $this->dom;
    }

    public function getOutput(): string
    {
        if ($this->dom && $this->domPossiblyModified) {
            $this->output = $this->dom->saveHTML($this->dom->childNodes->item(0)->childNodes->item(0));
            //$this->output = $this->dom->childNodes->item(0)->sav;
            $this->domPossiblyModified = false;
        }
        return $this->output;
    }
}