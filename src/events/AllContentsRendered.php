<?php

namespace foonoo\events;

use foonoo\sites\AbstractSite;

class AllContentsRendered
{
    private $site;

    public function __construct(AbstractSite $site)
    {
        $this->site = $site;
    }
}
