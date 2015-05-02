<?php
namespace nyansapow\processors\api;

abstract class Source 
{
    protected $sitePath;
    
    public function setSitePath($sitePath)
    {
        $this->sitePath = $sitePath;
    }
    
    abstract public function getNamespaces();
    abstract public function getClasses($namespace);
    abstract public function getInterfaces($namespace);
    abstract public function getClassDetails($class);
}

