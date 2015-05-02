<?php
namespace nyansapow\processors\api;

/**
 * Description of PhpdocSource
 *
 * @author ekow
 */
class Phpdox extends Source
{
    private $index;
    
    private function getNamespacePath($namespace)
    {
        return str_replace('\\', '/',$namespace) . ($namespace != '' ? '/' : 'global_namespace/');
    }
    
    private function getNamespaceName($namespace)
    {
        return $namespace == '' ? 'Global Namespace' : $namespace;
    }    
    
    public function getNamespaces() 
    {
        $this->index = simplexml_load_file("{$this->sourcePath}/xml/index.xml");
        $namespaces = [];
        
        // Flatten out namespaces
        foreach($this->index->namespace as $namespace)
        {
            $namespaces[] = array(
                'sort_field' => $namespace['name'],
                'name' => $this->getNamespaceName($namespace['name']),
                'path' => $this->getNamespacePath($namespace['name']),
                'namespace' => $namespace
            );
        }
        
        uasort(
            $namespaces,
            function($a, $b)
            {
                return strcmp($a['sort_field'], $b['sort_field']);
            }
        );      
        
        return $namespaces;
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
        
        uasort(
            $flat,
            function($a, $b)
            {
                return strcmp($a['name'], $b['name']);
            }
        );        
        
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

    public function getClassDetails($class) 
    {
        $classXml = simplexml_load_file("{$this->sourcePath}/xml/{$class["item"]['xml']}");
        $namespacePath = $this->getNamespacePath($classXml['namespace']);
        
        $constants = array();
        $properties = array();
        $methods = array();
        
        foreach($classXml->constant as $constant)
        {
            $constants[] = array(
                'name' => $constant['name'],
                'summary' => $constant->docblock->description['compact'],
                'details' => $constant->docblock->description,
                'type' => $constant->docblock->var["type"],
                'value' => $constant['value'],
                'link' => "constant_" . strtolower($constant['name'])
            );
        }
        
        foreach($classXml->member as $member)
        {
            $properties[] = array(
                'name' => $member['name'],
                'summary' => $member->docblock->description['compact'],
                'details' => $member->docblock->description,
                'type' => $member->docblock->var->type ? $member->docblock->var->type['name'] : $member->docblock->var["type"],
                'type_reference' => $member->docblock->var->type['full_name'],
                'visibility' => $member['visibility'],
                'default' => $member['default'],
                'link' => "member_" . strtolower($member['name'])
                   
            );
        }
        
        foreach($classXml->method as $method)
        {
            $parameters = [];
            foreach($method->parameter as $parameter)
            {
                $parameters[(string)$parameter['name']] = array(
                    'name' => $parameter['name'],
                    'type' => $parameter['type'] == '{unknown}' ? '' : $parameter['type']
                );
            }

            if($method->dockblock->param)
            {
                foreach($method->docblock->param as $parameter)
                {
                    $parameters[(string)$parameter['name']]['description'] = $parameter['description'];
                    $parameters[(string)$parameter['name']]['type'] = $parameter['type'];
                }
            }
            
            $methods[] = array(
                'name' => $method['name'],
                'summary' => $method->docblock->description['compact'],
                'details' => \nyansapow\TextRenderer::render($method->docblock->description, 'description.md'),
                'type' => $method->docblock->var["type"],
                'visibility' => $method['visibility'],
                'parameters' => $parameters,
                'static' => (string)$method['static'] === 'true',
                'abstract' => (string)$method['abstract'] === 'true',
                'return' => array(
                    'type' => $method->docblock->return['type'],
                    'description' => $method->docblock->return['description']
                ),
                'link' => "method_" . strtolower($method['name'])
            );
        }
        
        return array(
            'details' => $classXml->docblock->description,
            'constants' => $constants,
            'properties' => $properties,
            'methods' => $methods
        );
    }
}
