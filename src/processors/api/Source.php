<?php
namespace nyansapow\processors\api;

abstract class Source 
{
    protected $sourcePath;
    
    public function setSourcePath($sourcePath)
    {
        $this->sourcePath = $sourcePath;
    }
    
    abstract public function getNamespaces();
    abstract public function getClasses($namespace);
    abstract public function getInterfaces($namespace);
    abstract public function getClassDetails($class);
}

