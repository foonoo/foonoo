<?php


namespace foonoo\content;


/**
 * Base class for all content types in foonoo.
 * 
 */
abstract class Content
{
    /**
     * @var string
     */
    protected $sitePath;

    /**
     * @var string
     */
    protected $destination;

    /**
     * Get the meta-data for a given content object.
     * @return array
     */
    abstract public function getMetaData(): array;

    /**
     * Render the output of the content.
     * @return string
     */
    abstract public function render(): string;

    /**
     * 
     * @param type $sitePath
     * @return Content
     */
    public function setSitePath($sitePath): Content
    {
        $this->sitePath = $sitePath;
        return $this;
    }

    public function getFullDestination(): string
    {
        return $this->sitePath . "/" . $this->getDestination();
    }

    public function getDestination(): string
    {
        return $this->destination;
    }
}