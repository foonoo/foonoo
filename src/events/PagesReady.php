<?php


namespace nyansapow\events;


use nyansapow\content\AutomaticContentFactory;
use nyansapow\content\ContentInterface;

class PagesReady extends Event
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

    public function addPage(ContentInterface $page)
    {
        $this->pages[] = $page;
    }

    public function removePage(ContentInterface $page)
    {
        $index = array_search($page, $this->pages);
        if($index) {
            array_splice($this->pages, $index, 1);
        }
    }
}
