<?php


namespace nyansapow\sites;


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