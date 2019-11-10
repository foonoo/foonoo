<?php


namespace nyansapow\sites;


class SiteFactory
{
    private $pageFactory;

    public function __construct(PageFactory $pageFactory)
    {
        $this->pageFactory = $pageFactory;
    }

    public function create($type)
    {
        $class = "\\nyansapow\\sites\\" . ucfirst($type) . "Site";
        /** @var AbstractSite $instance */
        $instance = new $class();
        $instance->setPageFactory($this->pageFactory);
        return $instance;
    }
}
