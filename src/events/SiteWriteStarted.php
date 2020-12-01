<?php


namespace foonoo\events;


use foonoo\sites\AbstractSite;

class SiteWriteStarted
{
    private $site;

    public function __construct($site)
    {
        $this->site = $site;
    }

    public function getSite() : AbstractSite
    {
        return $this->site;
    }
}
