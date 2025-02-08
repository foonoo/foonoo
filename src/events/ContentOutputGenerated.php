<?php

namespace foonoo\events;


use Dom\Element;
use Dom\HTMLDocument;
use Dom\HTMLElement;
use Dom\Node;

/**
 * This event is triggered after the output of any content is generated and ready to be written.
 */
class ContentOutputGenerated extends BaseOutputEvent
{
    private ?HTMLElement $dom = null { get { return $this->dom; }}

    public function getDOM(): ?Node
    {
        // Create a DOM tree for objects that are possibly themed
        if ($this->dom === null && $this->hasDOM()) {
            $this->dom = HTMLDocument::createFromString("<!DOCTYPE html><html><body>{$this->output}</body></html>", LIBXML_NOERROR)->querySelector("body");
        }
        $this->domPossiblyModified = true;
        return $this->dom;
    }

    public function getOutput(): string
    {
        if ($this->dom !== null && $this->domPossiblyModified) {
            $this->output = $this->dom->innerHTML;
            $this->domPossiblyModified = false;
        }
        return $this->output;
    }
}
