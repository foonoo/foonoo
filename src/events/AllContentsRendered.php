<?php

namespace foonoo\events;

use foonoo\sites\AbstractSite;

class AllContentsRendered
{
    private AbstractSite $site;

    public function __construct(AbstractSite $site)
    {
        $this->site = $site;
    }
    
    public function getSite() : AbstractSite
    {
        return $this->site;
    }
}
