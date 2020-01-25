<?php


namespace nyansapow\events;


use nyansapow\sites\AbstractSite;

class Event
{
    private $site;

    public function setSite(AbstractSite $site = null)
    {
        $this->site = $site;
    }
}