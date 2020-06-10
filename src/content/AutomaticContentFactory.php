<?php

namespace foonoo\content;


use foonoo\sites\AbstractSite;

class AutomaticContentFactory
{
    private $contentFactories = [];
    private $copiedContentFactory;

    public function __construct(CopiedContentFactory $copiedContentFactory)
    {
        $this->copiedContentFactory = $copiedContentFactory;
    }

    public function register(callable $test, ContentFactory $contentFactory)
    {
        $this->contentFactories[] = ['test' => $test, 'instance' => $contentFactory];
    }

    /**
     * Create a new Content object
     *
     * @param string $source
     * @param string $destination
     * @return Content
     */
    public function create(string $source, string $destination) : Content
    {
        foreach ($this->contentFactories as $factory) {
            if($factory['test'](['source' => $source, 'destination' => $destination])) {
                return $factory['instance']->create($source, $destination);
            }
        }
        return $this->copiedContentFactory->create($source, $destination);
    }
}
