<?php
namespace nyansapow\processors\api;

abstract class Source 
{
    protected $sitePath;
    
    public function setSitePath($sitePath)
    {
        $this->sitePath = $sitePath;
    }
    
    public function getSitePath()
    {
        return $this->sitePath;
    }
    
    protected function sortItems($array, $sortField)
    {
        uasort(
            $array,
            function($a, $b) use($sortField)
            {
                return strcmp($a[$sortField], $b[$sortField]);
            }
        );            
        return $array;
    }
    
    abstract public function getNamespaces();
    abstract public function getClasses($namespace);
    abstract public function getInterfaces($namespace);
    abstract public function getClassDetails($class);
}

