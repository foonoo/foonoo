<?php

namespace foonoo\content;

/**
 * Content that wraps around another to convert it into an index page.
 */
class IndexWrapper extends Content implements ThemableInterface
{
    /**
     * The content to be wrapped.
     */
    private ThemableInterface|Content $content;
    
    /**
     * Create a new index wrapper.
     * 
     * @param Content $content
     */
    public function __construct(Content $content)
    {
        $this->content = $content;
        $this->destination = "index.html";
    }
    
    /**
     * Get the meta-data.
     * 
     * @return array
     */
    public function getMetaData(): array
    {
        $metaData = $this->content->getMetaData();
        $metaData['toc-skip'] = true;
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
