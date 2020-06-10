<?php


namespace foonoo\content;


use foonoo\content\ContentFactory;
use foonoo\content\Content;
use foonoo\content\CopiedContent;
use foonoo\sites\AbstractSite;

class CopiedContentFactory implements ContentFactory
{

    public function create(string $source, string $destination): Content
    {
        return new CopiedContent($source, $destination);
    }
}