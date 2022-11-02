<?php


namespace foonoo\sites;


interface SiteFactoryInterface
{
    public function create() : AbstractSite;
}