<?php
namespace nyansapow\processors;

use \nyansapow\Processor;
use \nyansapow\Nyansapow;
use \ntentan\honam\TemplateEngine;

class Api extends Processor
{
    private $namespaces = array();
    private $templateData = array();
    private $source;
    
    public function init()
    {
        $this->setTheme('api');
    }
    
    private function getSource()
    {
        if($this->source === null)
        {
            $sourceClass = "\\nyansapow\\processors\\api\\" . ucfirst($this->settings['source']);
            $this->source = new $sourceClass($this->getSourcePath(''));
        }
        return $this->source;
    }
    
    public function outputSite() 
    {
        $this->namespaces = $this->sort($this->getSource()->getNamespaces());
        
        foreach($this->namespaces as $i => $namespace)
        {
            $this->namespaces[$i]['link'] = "{$namespace['path']}index.html";
            $this->generateNamespaceDoc($namespace);
        }
                
        $this->setOutputPath('index.html');
        $this->outputPage(
            TemplateEngine::render(
                'home',
                array(
                    'title' => $this->settings['name'],
                    'namespaces' => $this->namespaces
                )
            ),
            array(
                'namespaces' => $this->namespaces
            )
        );
    }
    
    private function sort($items)
    {
        uasort($items, function($a, $b){
            return strcmp($a['name'], $b['name']);
        });
        return $items;
    }
    
    private function generateClassDoc($class, $type = 'class')
    {
        $source = $this->getSource();
        $path = "{$class['path']}.html";
        $this->setOutputPath($path);    
        $classDetails = $source->getClassDetails($class);
        
        $this->templateData['title'] = $class['name'];
        $this->templateData['path'] = $path;
        $this->outputPage(
            TemplateEngine::render(
                'class',
                array(
                    'class' => $class['name'],
                    'type' => $type,
                    'namespace' => $class['namespace'],
                    'summary' => $class['summary'],
                    'details' => $classDetails['details'],
                    'constants' => $this->sort($classDetails['constants']),
                    'properties' => $this->sort($classDetails['properties']),
                    'methods' => $this->sort($classDetails['methods']),
                    'extends' => $classDetails['extends']
                )
            ),
            $this->templateData
        );
    }
    
    private function generateNamespaceDoc($namespace)
    {
        $source = $this->getSource();
        $namespacePath = $namespace['path'];
        Nyansapow::mkdir($this->getDestinationPath($namespacePath));
        
        $classes = $this->sort($source->getClasses($namespace));
        $interfaces  = $this->sort($source->getInterfaces($namespace));
        $path = "{$namespacePath}index.html";
        
        $this->templateData = array(
            'namespaces' => $this->namespaces,
            'classes' => $classes,
            'interfaces' => $interfaces,
            'namespace' => $namespace,
            'source_parser' => $this->source->getDescription(),
            'namespace_path' => $namespacePath
        );        
        
        foreach($classes as $class)
        {
            $this->generateClassDoc($class);
        }
        
        foreach($interfaces as $interface)
        {
            $this->generateClassDoc($interface, 'interface');
        }
                
        $this->setOutputPath($path);
        $this->templateData['path'] = null;
        $this->outputPage(
            TemplateEngine::render(
                'namespace', 
                array(
                    'namespace' => $namespace['label'],
                    'classes' => $classes,
                    'interfaces' => $interfaces,
                    'site_path' => $this->getSitePath()
                )
            ),
            $this->templateData
        );
    }
    
    public function setOutputPath($path) 
    {
        parent::setOutputPath($path);
        $this->getSource()->setSitePath($this->getSitePath());
    }
}
