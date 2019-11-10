<?php


namespace nyansapow\sites;


class BlogSite extends AbstractSite
{

    public function getPages() : array
    {
        return [];
    }

    public function getType() : string
    {
        return 'blog';
    }
}