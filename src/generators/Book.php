<?php
namespace nyansapow\processors;

class Book extends Wiki
{
    public function init()
    {
        $this->settings['mode'] = 'book';
        parent::init();
    }

    /**
     * @throws \ntentan\honam\exceptions\FileNotFoundException
     */
    protected function outputIndexPage()
    {
        $this->setOutputPath('index.html');
        $this->outputWikiPage(reset($this->pages));
    }
}
