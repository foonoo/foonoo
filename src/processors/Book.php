<?php
namespace nyansapow\processors;

class Book extends Wiki
{
    public function init()
    {
        $this->settings['mode'] = 'book';
        parent::init();
    }
}
