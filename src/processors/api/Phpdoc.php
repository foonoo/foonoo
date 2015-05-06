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
                        $newItem['parameters'][(string)$tag['variable']]['description'] = strip_tags((string)$tag['description']);
                    }
                    break;
                case 'return':
                    $newItem['return'] = array(
                        'type' => $this->getTypeLink($tag['type']),
                        'description' => (string)$tag['description']
                    );
                    
                    $newItem['type'] = $newItem['return']['type'];
                    break;
                case 'throws':
                    $newItem['throws'][] = array(
                        'type' => $this->getTypeLink($tag['type'])
                    );
                    break;
                case 'see':
                    $newItem['sees'][] = array(
                        'type' => $this->getTypeLink($tag['link'])
                    );
                    break;
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
                'details' => \nyansapow\TextRenderer::render((string)$item->docblock->{'long-description'}, 'description.md'),
                'type' => array(),
                'sees' => array(),
                'visibility' => $item['visibility'],
                'value' => (string)$item->value,
                'static' => (string)$item['static'] == 'true',
                'final' => (string)$item['final'] == 'true',
                'default' => (string)$item->default,
                'abstract' => (string)$item['abstract'] === 'true',                             
                'link' => "#{$type}_{$item->name}"
            )
        );
        if($item->docblock->tag)
        {
            $details = $this->processTags($item->docblock->tag, $details);
        }
        
        return $details;
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
                    'type' => $this->getTypeLink($argument->type),
                    'byreference' => $argument['by_reference'] === 'true'
                ];
            }
            
            $newMethod = $this->getBasicDetails(
                $method, 'method', 
                array(
                    'parameters' => $parameters,
                    'return' => [],
                    'throws' => []
                )
            );
            
            $newMethod['abstract'] = (string)$method['abstract'] == 'true';
            
            $methods[] = $newMethod;
        }
        
        $classDetails = $this->getBasicDetails($class['item'], 'class');
        $classDetails['extends'] = $this->getTypeLink($class['item']->extends);
        $classDetails['constants'] = $constants;
        $classDetails['properties'] = $properties;
        $classDetails['methods'] = $methods;
        
        return $classDetails;
    }
    
    public function flattenOutItems($items, $namespace)
    {
        $namespacePath = $this->getNamespacePath($namespace['name']);
        $flat = [];
        foreach($items as $item)
        {
            $flat[] = [
                "name" => $item->name,
                'namespace' => $namespace['name'],
                'description' => $item->docblock->description,
                'path' => "$namespacePath{$item->name}",
                'item' => $item
            ];
        }
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
                'name' => $namespace,
                'label' => $this->getNamespaceName($namespace),
                'path' => $this->getNamespacePath($namespace),
            );
        }
        return $namespaces;
    }

    public function getDescription() 
    {
        return 'PHPDocumentator';
    }
}
