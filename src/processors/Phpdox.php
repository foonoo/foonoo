<?php
namespace nyansapow\processors;

use \nyansapow\Processor;
use \nyansapow\Nyansapow;
use \ntentan\honam\TemplateEngine;

class Phpdox extends Processor
{
    private $index;
    private $namespaces = array();
    
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
    
    private function generateNamespaceDoc($namespace)
    {
        $namespacePath = str_replace('\\', '/',$namespace['name']);
        Nyansapow::mkdir($this->getDestinationPath($namespacePath));
        $this->setOutputPath("{$namespacePath}/index.html");
        
        $classes = $this->flattenOutItems($namespace->class, $namespacePath);
        $interfaces  = $this->flattenOutItems($namespace->interface, $namespacePath);
                
        $this->outputPage(
            TemplateEngine::render(
                'namespace.mustache', 
                array(
                    'namespace' => $namespace['name'],
                    'classes' => $classes,
                    'interfaces' => $interfaces
                )
            ),
            array(
                'namespaces' => $this->namespaces,
                'classes' => $classes,
                'interfaces' => $interfaces,
                'namespace' => $namespace
            )
        );
    }
}

