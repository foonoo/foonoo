<?php


namespace foonoo\events;

use foonoo\sites\AbstractSite;

class SiteObjectCreated
{
    private $site;

    public function __construct(AbstractSite $site)
    {
        $this->site = $site;
    }

    public function getSite(): AbstractSite
    {
        return $this->site;
    }

}