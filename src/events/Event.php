<?php


namespace foonoo\events;


use foonoo\sites\AbstractSite;

class Event
{
    private $site;

    public function setSite(AbstractSite $site = null)
    {
        $this->site = $site;
    }
}