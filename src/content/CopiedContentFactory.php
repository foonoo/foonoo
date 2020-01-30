<?php


namespace nyansapow\content;


use nyansapow\content\ContentFactory;
use nyansapow\content\Content;
use nyansapow\content\CopiedContent;
use nyansapow\sites\AbstractSite;

class CopiedContentFactory implements ContentFactory
{

    public function create(string $source, string $destination): Content
    {
        return new CopiedContent($source, $destination);
    }
}