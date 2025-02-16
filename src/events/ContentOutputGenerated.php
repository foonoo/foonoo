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
    public function getOutput(): string
    {
        // Override output with any modifications made to the DOM.
        if ($this->isDomAccessed()) {
            $dom = $this->getDom();
            $this->output = $dom->querySelector('body')->innerHTML;
            $this->resetDom();
        }
        return $this->output;
    }
}
