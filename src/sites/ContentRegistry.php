<?php

namespace nyansapow\sites;

use ntentan\honam\TemplateRenderer;
use nyansapow\text\HtmlRenderer;

class ContentRegistry
{
    private $contentFactories = [];

    public function register(callable $test, ContentFactoryInterface $contentFactory)
    {
        $this->contentFactories[] = ['test' => $test, 'instance' => $contentFactory];
    }

    /**
     * Create a new Content object
     *
     * @param $source
     * @param $destination
     * @param array $data
     * @return ContentInterface
     */
    public function create($source, $destination, $data=[]) : ContentInterface
    {
        foreach ($this->contentFactories as $factory)
        {
            if($factory['test'](['source' => $source, 'destination' => $destination, 'data' =>$data])) {
                return $factory['instance']->create($source, $destination, $data);
            }
        }
    }
}
