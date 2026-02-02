<?php


namespace foonoo\content;


/**
 * Base class for all content types in foonoo.
 */
abstract class Content
{
    /**
     * @var string
     */
    protected string $sitePath;

    /**
     * @var string
     */
    protected string $destination;

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
     * Return an ID for the content.
     * 
     * IDs do not necessarily have to be unique across a site. It is just recommended that different content classes
     * containing the same content but writing to different destinations, or styled differently share the same ID.
     * A proper way to obtain unique IDs for individual content items is through the destination path.
     */
    abstract public function getID(): string;    

    /**
     * Set the path for the current site.
     * 
     * @param string $sitePath
     * @return Content
     */
    public function setSitePath(string $sitePath): Content
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