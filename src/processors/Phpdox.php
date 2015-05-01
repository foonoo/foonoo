<?php
namespace nyansapow\processors;

use \nyansapow\Processor;
use \nyansapow\Nyansapow;
use \ntentan\honam\TemplateEngine;

class Phpdox extends Processor
{
    private $index;
    private $namespaces = array();
    private $templateData = array();
    
    public function init()
    {
        $this->setTheme('api');
    }
    
    private function getNamespacePath($namespace)
    {
        return str_replace('\\', '/',$namespace) . ($namespace != '' ? '/' : 'global_namespace/');
    }
    
    private function getNamespaceName($namespace)
    {
        return $namespace == '' ? 'Global Namespace' : $namespace;
    }
    
    public function outputSite() 
    {
        $this->index = simplexml_load_file($this->getSourcePath('index.xml'));
        
        // Flatten out namespaces
        foreach($this->index->namespace as $namespace)
        {
            $this->namespaces[] = array(
                'sort_field' => $namespace['name'],
                'name' => $this->getNamespaceName($namespace['name']),
                'path' => $this->getNamespacePath($namespace['name'])
            );
        }
        
        uasort(
            $this->namespaces,
            function($a, $b)
            {
                return strcmp($a['sort_field'], $b['sort_field']);
            }
        );
        
        foreach($this->index->namespace as $namespace)
        {
            $this->generateNamespaceDoc($namespace);
        }
    }
    
    private function flattenOutItems($items, $namespacePath)
    {
        $flat = [];
        foreach($items as $item)
        {
            $flat[] = [
                "name" => $item['name'],
                "description" => $item['description'],
                'path' => "$namespacePath{$item['name']}"
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
    
    private function generateClassDoc($class, $type = 'class')
    {
        $classXml = simplexml_load_file($this->getSourcePath($class["xml"]));
        $namespacePath = $this->getNamespacePath($classXml['namespace']);
        $path = "{$namespacePath}{$class['name']}.html";
        $this->setOutputPath($path);
        
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
                'type' => $member->docblock->var["type"],
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
        
        $this->templateData['title'] = $classXml['name'];
        $this->templateData['path'] = $path;
        $this->templateData['namespace_path'] = $namespacePath;
        $this->outputPage(
            TemplateEngine::render(
                'class',
                array(
                    'class' => $classXml['name'],
                    'type' => $type,
                    'namespace' => $classXml['namespace'],
                    'summary' => $classXml->docblock->description['compact'],
                    'detail' => $classXml->docblock->description,
                    'constants' => $constants,
                    'properties' => $properties,
                    'methods' => $methods
                )
            ),
            $this->templateData
        );
    }
    
    private function generateNamespaceDoc($namespace)
    {
        $namespacePath = $this->getNamespacePath($namespace['name']);
        Nyansapow::mkdir($this->getDestinationPath($namespacePath));
        
        $classes = $this->flattenOutItems($namespace->class, $namespacePath);
        $interfaces  = $this->flattenOutItems($namespace->interface, $namespacePath);
        $path = "{$namespacePath}index.html";
        
        $this->templateData = array(
            'namespaces' => $this->namespaces,
            'classes' => $classes,
            'interfaces' => $interfaces,
            'namespace' => $namespace,
        );        
        
        foreach($namespace->class as $class)
        {
            $this->generateClassDoc($class);
        }
        
        foreach($namespace->interface as $interface)
        {
            $this->generateClassDoc($interface, 'interface');
        }
                
        $this->setOutputPath($path);
        $this->templateData['namespace_path'] = $namespacePath;
        $this->templateData['path'] = null;
        $this->outputPage(
            TemplateEngine::render(
                'namespace', 
                array(
                    'namespace' => $this->getNamespaceName($namespace['name']),
                    'classes' => $classes,
                    'interfaces' => $interfaces,
                    'site_path' => $this->getSitePath()
                )
            ),
            $this->templateData
        );
    }
}
