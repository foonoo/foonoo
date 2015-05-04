<?php
namespace nyansapow\processors\api;

/**
 * Description of PhpdocSource
 *
 * @author ekow
 */
class Phpdox extends Source
{
    use PhpApiUtils;
    
    private $index;
    private $sourcePath;
    
    public function __construct($sourcePath)
    {
        $this->index = simplexml_load_file("{$sourcePath}/xml/index.xml");
        $this->sourcePath = $sourcePath;
    } 
    
    public function getNamespaces() 
    {
        $namespaces = [];
        
        // Flatten out namespaces
        foreach($this->index->namespace as $namespace)
        {
            $namespaces[] = array(
                'name' => $namespace['name'],
                'label' => $this->getNamespaceName($namespace['name']),
                'path' => $this->getNamespacePath($namespace['name']),
                'namespace' => $namespace
            );
        }
        
        return array_values($namespaces);
    }
    
    private function flattenOutItems($items, $namespace)
    {
        $namespacePath = $this->getNamespacePath($namespace);
        $flat = [];
        foreach($items as $item)
        {
            $flat[] = [
                "name" => $item['name'],
                'namespace', $namespace,
                "description" => $item['description'],
                'path' => "$namespacePath{$item['name']}",
                'item' => $item
            ];
        }
        
        return $flat;
    }    

    public function getClasses($namespace) 
    {
        return $this->flattenOutItems(
            $namespace['namespace']->class, 
            $namespace['name']
        );
    }

    public function getInterfaces($namespace) 
    {
        return $this->flattenOutItems(
            $namespace['namespace']->interface, 
            $namespace['name']
        );
    }
    
    private function getBasicDetails($item, $type)
    {
        $detail = [
            'name' => (string)$item['name'],
            'summary' => (string)$item->docblock->description['compact'],
            'details' => \nyansapow\TextRenderer::render((string)$item->docblock->description, 'description.md'),
            'type' => $this->getTypeLink($item->docblock->var["type"]),
            'value' => (string)$item['value'],
            'visibility' => (string)$item['visibility'],
            'default' => (string)$item['default'],
            'static' => (string)$item['static'] === 'true',
            'abstract' => (string)$item['abstract'] === 'true',     
            'final' => (string)$item['final'] === 'true',            
            'link' => "{$type}_" . strtolower($item['name']),
            'sees' => array()
        ];
            
        if($item->docblock->see)
        {
            foreach($item->docblock->see as $see)
            {
                $detail['sees'][] = array(
                    'type' => $this->getTypeLink($see['value'])
                );
            }
        }            
        
        return $detail;
    }

    public function getClassDetails($class) 
    {
        $classXml = simplexml_load_file("{$this->sourcePath}/xml/{$class["item"]['xml']}");
        
        $constants = array();
        $properties = array();
        $methods = array();
        
        foreach($classXml->constant as $constant)
        {
            $constants[] = $this->getBasicDetails($constant, 'constant');
        }
        
        foreach($classXml->member as $member)
        {
            $member['name'] = '$' . $member['name'];
            $properties[] = $this->getBasicDetails($member, 'property');
        }
        
        foreach($classXml->method as $method)
        {
            $parameters = [];
            $throws = [];
            $sees = [];
            
            foreach($method->parameter as $parameter)
            {
                $parameters['$' . (string)$parameter['name']] = array(
                    'name' => '$' . $parameter['name'],
                    'type' => $this->getTypeLink($parameter['type'] == '{unknown}' ? '' : $parameter['type']),
                    'byreference' => $parameter['byreference'] === 'true'
                );
            }

            if($method->docblock->param)
            {
                foreach($method->docblock->param as $parameter)
                {
                    if(isset($parameters[(string)$parameter['variable']]))
                    {
                        $parameters[(string)$parameter['variable']]['description'] = (string)$parameter['description'];
                    }
                }
            }
            
            if($method->docblock->throws)
            {
                foreach($method->docblock->throws as $throw)
                {
                    $throws[] = array(
                        'type' => $this->getTypeLink($throw->type['full'])
                    );
                }
            }
            
            $newMethod = $this->getBasicDetails($method, 'method');
            $newMethod['parameters'] = $parameters;
            $newMethod['throws'] = $throws;
            $newMethod['return'] = array(
                'type' => $this->getTypeLink($method->docblock->return['type']),
                'description' => $method->docblock->return['description']
            );
            $newMethod['type'] = $newMethod['return']['type'];
            $methods[] = $newMethod;
        }
        
        $class = $this->getBasicDetails($classXml, 'class');
        $class['constants'] = $constants;
        $class['properties'] = $properties;
        $class['methods'] = $methods;
        
        return $class;
    }
}
