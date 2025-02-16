<?php

namespace foonoo\events;


use Dom\HTMLDocument;
use Dom\Node;

/**
 * This event is triggered after the output of any content is generated and ready to be written.
 */
class ContentLayoutApplied extends BaseOutputEvent
{

    public function getOutput(): string
    {
        if ($this->isDomAccessed()) {
            $dom = $this->getDom();
            $this->output = $dom->saveHTML();
            $this->resetDom();
        }
        return $this->output;
    }
}
