<?php
namespace foonoo\content;

class CopiedContentFactory implements ContentFactory
{
    public function create(string $source, string $destination): Content
    {
        return new CopiedContent($source, $destination);
    }
}