<?php
namespace nyansapow\processors;

class Site extends \nyansapow\Processor
{
    public function outputSite()
    {
        $this->getFiles();
    }
}