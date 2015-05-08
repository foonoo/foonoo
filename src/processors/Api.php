<?php
namespace nyansapow\processors;

use \nyansapow\Processor;
use \nyansapow\Nyansapow;
use \ntentan\honam\TemplateEngine;

class Api extends Processor
{
    private $namespaces = array();
    private $templateData = array();
    private $typeIndex = array();
    
    /**
     *
     * @var Source
     */
    private $source;
    
    public function init()
    {
        $this->setTheme('api');
    }
    
    /**
     * 
     * @return Source
     */
    private function getSource()
    {
        if($this->source === null)
        {
            $sourceClass = "\\nyansapow\\processors\\api\\" . ucfirst($this->settings['source']);
            $this->source = new $sourceClass($this->getSourcePath(''));
        }
        return $this->source;
    }
    
    private function extractNamespaceInfo()
    {
        $this->namespaces = $this->sort($this->getSource()->getNamespaces());
        
        foreach($this->namespaces as $i => $namespace)
        {
            $this->namespaces[$i]['link'] = "{$namespace['path']}index.html";
            $this->namespaces[$i]['name'] = $namespace['label'];
            
            $this->namespaces[$i]['classes'] = $this->extractItemDetails(
                $this->getSource()->getClasses($namespace)
            );
            
            $this->namespaces[$i]['interfaces'] = $this->extractItemDetails(
                $this->getSource()->getInterfaces($namespace)
            );
        }        
    }
    
    private function extractItemDetails($items)
    {
        $detailedItems = $this->sort($items);
        foreach($detailedItems as $i => $item)
        {
            $detailedItems[$i]['details'] = $this->getSource()->getClassDetails($item);
            $this->typeIndex[$item['namespace']. '\\' . $item['name']] = "{$this->baseDir}{$item['path']}.html";
        }
        return $detailedItems;
    }
    
    public function outputSite() 
    {
        $this->extractNamespaceInfo();
        
        \nyansapow\TextRenderer::setTypeIndex($this->typeIndex);
        
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
                'namespaces' => $this->namespaces,
                'title' => 'Namespaces'
            )
        );   
        
        foreach($this->namespaces as $namespace)
        {
            $this->generateNamespaceDoc($namespace);
        }
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
        $path = "{$class['path']}.html";
        $this->setOutputPath($path);    
        $classDetails = $class['details'];
        
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
        $namespacePath = $namespace['path'];
        Nyansapow::mkdir($this->getDestinationPath($namespacePath));
        
        $path = "{$namespacePath}index.html";
        
        $this->templateData = array(
            'namespaces' => $this->namespaces,
            'namespace' => $namespace,
            'source_parser' => $this->source->getDescription(),
            'namespace_path' => $namespacePath
        );        
        
        foreach($namespace['classes'] as $class)
        {
            $this->generateClassDoc($class);
        }
        
        foreach($namespace['interfaces'] as $interface)
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
                    'classes' => $namespace['classes'],
                    'interfaces' => $namespace['interfaces'],
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
