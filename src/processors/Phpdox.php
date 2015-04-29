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
    
    public function outputSite() 
    {
        $this->index = simplexml_load_file($this->getSourcePath('index.xml'));
        
        // Flatten out namespaces
        foreach($this->index->namespace as $namespace)
        {
            $this->namespaces[] = array(
                'name' => $namespace['name'],
                'path' => str_replace('\\', '/', $namespace['name'])
            );
        }
        
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
                'path' => "$namespacePath/{$item['name']}"
            ];
        }
        
        return $flat;
    }
    
    private function generateClassDoc($class)
    {
        $classXml = simplexml_load_file($this->getSourcePath($class["xml"]));
        $namespacePath = str_replace('\\', '/',$classXml['namespace']);
        $this->setOutputPath("{$namespacePath}/{$class['name']}.html");
        $this->outputPage(
            TemplateEngine::render(
                'class',
                array(
                    'class' => $classXml['name'],
                    'namespace' => $classXml['namespace'],
                    'summary' => $classXml->docblock->description['compact'],
                    'detail' => $classXml->docblock->description
                )
            ),
            $this->templateData
        );
    }
    
    private function generateNamespaceDoc($namespace)
    {
        $namespacePath = str_replace('\\', '/',$namespace['name']);
        Nyansapow::mkdir($this->getDestinationPath($namespacePath));
        
        $classes = $this->flattenOutItems($namespace->class, $namespacePath);
        $interfaces  = $this->flattenOutItems($namespace->interface, $namespacePath);
        
        $this->templateData = array(
            'namespaces' => $this->namespaces,
            'classes' => $classes,
            'interfaces' => $interfaces,
            'namespace' => $namespace
        );        
        
        foreach($namespace->class as $class)
        {
            $this->generateClassDoc($class);
        }
                
        $this->setOutputPath("{$namespacePath}/index.html");
        $this->outputPage(
            TemplateEngine::render(
                'namespace', 
                array(
                    'namespace' => $namespace['name'],
                    'classes' => $classes,
                    'interfaces' => $interfaces,
                    'site_path' => $this->getSitePath()
                )
            ),
            $this->templateData
        );
    }
}

