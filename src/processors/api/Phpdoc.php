<?php
namespace nyansapow\processors\api;

/**
 * Description of PhpdocSource
 *
 * @author ekow
 */
class Phpdoc extends Source
{
    use PhpApiUtils;
        
    private $xml;
    
    public function __construct($source) 
    {
        $this->xml = simplexml_load_file("$source/structure.xml");
    }
    
    private function processTags($tags, $newItem)
    {
        foreach($tags as $tag)
        {
            switch($tag['name'])
            {
                case 'var': 
                    $newItem['type'] = $this->getTypeLink((string)$tag['type']);
                    break;
                case 'param': 
                    if(isset($newItem['parameters'][(string)$tag['variable']]))
                    {
                        $newItem['parameters'][(string)$tag['variable']]['description'] = (string)$tag['description'];
                    }
                    break;
                case 'return':
                    $newItem['return']['type'] = $this->getTypeLink($tag['type']);
                    $newItem['return']['description'] = $tag['description'];
            }
        }       
        return $newItem;
    }
    
    private function getBasicDetails($item, $type, $merge = array())
    {
        $details = array_merge(
            $merge,
            array(
                'name' => (string)$item->name,
                'summary' => (string)$item->docblock->description,
                'details' => (string)$item->docblock->{'long-description'},                
                'type' => array(),
                'visibility' => $item['visibility'],
                'value' => (string)$item->value,
                'static' => (string)$item['static'] == 'true',
                'default' => (string)$item->default,
                'link' => "{$type}_{$item->name}"
            )
        );
        return $this->processTags($item->docblock->tag, $details);
    }
    
    public function getClassDetails($class) 
    {
        $constants = array();
        $properties = array();
        $methods = array();
        
        foreach($class['item']->constant as $constant)
        {
            $constants[] = $this->getBasicDetails($constant, 'constant');
        }
        
        foreach($class['item']->property as $property)
        {                   
            $properties[] = $this->getBasicDetails($property, 'property');
        }
        
        foreach($class['item']->method as $method)
        {
            $parameters = [];
            foreach($method->argument as $argument)
            {
                $parameters[(string)$argument->name] = [
                    'name' => $argument->name,
                    'type' => $this->getTypeLink($argument->type)
                ];
            }
            
            $newMethod = $this->getBasicDetails(
                $method, 'method', 
                array(
                    'parameters' => $parameters,
                    'return' => array(
                        'type' => array(),
                        'details' => null
                    )
                )
            );
            
            $newMethod['abstract'] = (string)$method['abstract'] == 'true';
            
            $methods[] = $newMethod;
        }
        
        return array(
            'details' => $class['item']->docblock->{'long-description'},
            'constants' => $constants,
            'properties' => $properties,
            'methods' => $methods
        );
    }
    
    public function flattenOutItems($items, $namespace)
    {
        $namespacePath = $this->getNamespacePath($namespace['name']);
        $flat = [];
        foreach($items as $item)
        {
            $flat[(string)$item->name] = [
                "name" => $item->name,
                'namespace' => $namespace['name'],
                'description' => $item->docblock->description,
                'path' => "$namespacePath{$item->name}",
                'item' => $item
            ];
        }
        ksort($flat);
        return $flat;        
    }

    public function getClasses($namespace) 
    {
        return $this->flattenOutItems(
            $this->xml->xpath("/project/file/class[@namespace='{$namespace['name']}']"), 
            $namespace
        );
    }

    public function getInterfaces($namespace) 
    {    
        return $this->flattenOutItems(
            $this->xml->xpath("/project/file/interface[@namespace='{$namespace['name']}']"), 
            $namespace
        );        
    }

    public function getNamespaces() 
    {
        $namespaces = array();
        $namespaceElements = $this->xml->xpath("/project/file/class/@namespace");
        foreach($namespaceElements as $namespaceElement)
        {
            $namespace = (string)$namespaceElement['namespace'];
            $namespaces[$namespace] = array(
                'sort_field' => $namespace,
                'name' => $this->getNamespaceName($namespace),
                'path' => $this->getNamespacePath($namespace),
            );
        }
        ksort($namespaces);
        return $namespaces;
    }
}
