<?php


namespace foonoo\events;


use foonoo\content\AutomaticContentFactory;
use foonoo\content\Content;

/**
 * An event dispatched when all the content needed for a site are completely generated.
 *
 * @package foonoo\events
 */
class ContentReady
{
    private $pages;
    private $contentFactory;

    public function __construct(array $pages, AutomaticContentFactory $contentFactory)
    {
        $this->pages = $pages;
        $this->contentFactory = $contentFactory;
    }

    public function getPages() : array
    {
        return $this->pages;
    }

    public function getContentFactory() : AutomaticContentFactory
    {
        return $this->contentFactory;
    }

    public function addPage(Content $page)
    {
        $this->pages[] = $page;
    }

    public function removePage(Content $page)
    {
        $index = array_search($page, $this->pages);
        if($index) {
            array_splice($this->pages, $index, 1);
        }
    }
}
