<?php

namespace foonoo\events;


use Dom\HTMLDocument;
use Dom\Node;

/**
 * This event is triggered after the output of any content is generated and ready to be written.
 */
class ContentLayoutApplied extends BaseOutputEvent
{
    private ?HTMLDocument $document = null;
    /**
     * If content has a DOM, you can use this to get it.
     */
    public function getDocument(): ?HTMLDocument
    {
        // Create a DOM tree for objects that are possibly themed
        if ($this->document === null && $this->hasDOM()) {
            $this->document = HTMLDocument::createFromString($this->output);
        }
        $this->domPossiblyModified = true;
        return $this->document;
    }

    /**
     * Get the output that was generated.
     *
     * @return string
     */
    public function getOutput(): string
    {
        if ($this->document !== null && $this->domPossiblyModified) {
            $this->output = $this->document->saveHtml();
            $this->domPossiblyModified = false;
        }
        return $this->output;
    }
}
