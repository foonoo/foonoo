<?php

namespace foonoo\content;

class IndexWrapper extends Content implements ThemableInterface
{
    private $content;
    
    public function __construct(Content $content)
    {
        $this->content = $content;
        $this->destination = "index.html";
    }
    
    public function getMetaData(): array
    {
        $metaData = $this->content->getMetaData();
        return $metaData;
    }

    public function render(): string
    {
        return $this->content->render();
    }

    public function getLayoutData()
    {
        return $this->content->getLayoutData();
    }

    public function getID(): string
    {
        return $this->content->getID();
    }

}
