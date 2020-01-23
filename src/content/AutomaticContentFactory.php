<?php

namespace nyansapow\content;


use nyansapow\sites\AbstractSite;
use nyansapow\content\ContentFactoryInterface;
use nyansapow\content\ContentInterface;

class AutomaticContentFactory
{
    private $contentFactories = [];

    public function register(callable $test, ContentFactoryInterface $contentFactory)
    {
        $this->contentFactories[] = ['test' => $test, 'instance' => $contentFactory];
    }

    /**
     * Create a new Content object
     *
     * @param AbstractSite $site
     * @param string $source
     * @param string $destination
     * @return ContentInterface
     */
    public function create(AbstractSite $site, string $source, string $destination) : ContentInterface
    {
        foreach ($this->contentFactories as $factory) {
            if($factory['test'](['source' => $source, 'destination' => $destination])) {
                return $factory['instance']->create($site, $source, $destination);
            }
        }
    }
}
