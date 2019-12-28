<?php


namespace nyansapow\content;


use nyansapow\content\BlogPostContent;

class BlogPageContent extends BlogPostContent
{
    protected $template = "page";

    public function getLayoutData()
    {
        $data = parent::getLayoutData();
        $data['page_type'] = 'page';
        return $data;
    }
}