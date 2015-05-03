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
        $this->namespaces = $this->getSource()->getNamespaces();
        
        foreach($this->namespaces as $namespace)
        {
            $this->generateNamespaceDoc($namespace);
        }
    }
    
    private function generateClassDoc($class, $type = 'class')
    {      
        $source = $this->getSource();
        $classDetails = $source->getClassDetails($class);
        $path = "{$class['path']}.html";
        $this->setOutputPath($path);    
        
        $this->templateData['title'] = $class['name'];
        $this->templateData['path'] = $path;
        $this->templateData['namespace_path'] = $namespacePath;
        $this->outputPage(
            TemplateEngine::render(
                'class',
                array(
                    'class' => $class['name'],
                    'type' => $type,
                    'namespace' => $class['namespace'],
                    'summary' => $class['summary'],
                    'details' => $classDetails['details'],
                    'constants' => $classDetails['constants'],
                    'properties' => $classDetails['properties'],
                    'methods' => $classDetails['methods']
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
        
        $classes = $source->getClasses($namespace);
        $interfaces  = $source->getInterfaces($namespace);
        $path = "{$namespacePath}index.html";
        
        $this->templateData = array(
            'namespaces' => $this->namespaces,
            'classes' => $classes,
            'interfaces' => $interfaces,
            'namespace' => $namespace,
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
        $this->templateData['namespace_path'] = $namespacePath;
        $this->templateData['path'] = null;
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
    
    public function setOutputPath($path) 
    {
        parent::setOutputPath($path);
        $this->getSource()->setSitePath($this->getSitePath());
    }
}
