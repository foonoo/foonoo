<?php
namespace nyansapow\processors\api;

/**
 * Description of PhpdocSource
 *
 * @author ekow
 */
class Phpdoc extends Source
{
    private $xml;
    
    public function __construct($source) 
    {
        $this->xml = simplexml_load_file("$source/api.xml");
    }
    
    public function getClassDetails($class) 
    {
        
    }

    public function getClasses($namespace) 
    {
        
    }

    public function getInterfaces($namespace) 
    {    
        
    }

    public function getNamespaces() 
    {
        
    }
}
