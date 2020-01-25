<?php


namespace nyansapow\content;

class BlogPageContent extends MarkupContent implements ThemableInterface
{
    protected $template = "page";

    public function getLayoutData()
    {
        return ['page_type' => 'post'];
    }
}
