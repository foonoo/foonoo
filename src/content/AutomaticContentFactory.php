<?php

namespace foonoo\content;


/**
 * Creates instances of Content classes for registered content types.
 * In cases where registered content types do not exist, files passed are wrapped with the CopiedContent type, which
 * allows files to be copied to the destination site without any processing.
 *
 * @package foonoo\content
 */
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
    public function create(string $source, string $destination): Content
    {
        foreach ($this->contentFactories as $factory) {
            if ($factory['test'](['source' => $source, 'destination' => $destination])) {
                return $factory['instance']->create($source, $destination);
            }
        }
        return $this->copiedContentFactory->create($source, $destination);
    }
}
