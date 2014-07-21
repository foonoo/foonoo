<?php
namespace nyansapow\processors;

class Doxygen extends \nyansapow\SiteProcessor
{
    protected function parseText($textNodes)
    {
        $html = '';
        
        foreach($textNodes->childNodes as $node)
        {
            switch($node->nodeType){
                case XML_ELEMENT_NODE:
                    $html .= $this->parseText($node);
                    break;
                case XML_TEXT_NODE:
                    $html .= $node->textContent;
                    break;
            }
        }
        
        return $html;
    }
    
    public function outputSite() 
    {
        // Search for the index.xml file
        $indexXml = $this->getDir() . 'xml/index.xml';
        if(!file_exists($indexXml)) return;
        $this->setLayout('doxygen.mustache', true);
                
        \nyansapow\Nyansapow::mkdir(self::$nyansapow->getDestination() . '/' . $this->getDir());
        
        // Load the index.xml file
        $index = simplexml_load_file($indexXml);
        
        // Get all the classes
        $classes = $index->xpath("/doxygenindex/compound[@kind='class']");
        
        // Keep separate classlist and classid arrays so as not to hurt the template engine
        $classesList = array();
        $classIds = array();
        foreach($classes as $class)
        {
            $classXml = simplexml_load_file($this->getDir() . "xml/{$class['refid']}.xml");
            $parents = $classXml->compounddef->basecompoundref;
            $classIds[] = $class['refid'];
            $classDescription = array(
                'name' => (string)$class->name,
                'link' => (string)$class->name . ".html",
                'brief' => $this->parseText(dom_import_simplexml($classXml->compounddef->briefdescription)),
                'description' => $this->parseText(dom_import_simplexml($classXml->compounddef->detaileddescription)),
                'access' => (string)$classXml->compounddef['prot'],
                'parents' => array()
            );
            
            foreach($parents as $parent)
            {
                $classDescription['parents'][] = array(
                    'name' => (string)$parent,
                    'refid' => (string)$parent['refid']
                );
            }
            
            $classesList[] = $classDescription;
        }
        
        //Write the index page lists summary of classes
        $m = new \Mustache_Engine();
        $sideMenuItems = $m->render(
            file_get_contents(self::$nyansapow->getHome() . "/themes/default/templates/doxygen_side_menu.mustache"),
            array(
                'classes' => $classesList
            )
        );
                
        $classList = $m->render(
            file_get_contents(self::$nyansapow->getHome() . "/themes/default/templates/doxygen_class_list.mustache"),
            array(
                'classes' => $classesList
            )
        );
        
        $this->outputPage(
            $this->getDir() . 'index.html',
            array(
                'body' => $classList,
                'side-list' => $sideMenuItems,
                'page_title' => "All Classes",
                'site_name' => $this->settings['site-name'],
                'date' => date('jS F, Y H:i:s'),
                'assets_location' => \nyansapow\SiteProcessor::getAssetsLocation($this->getDir())
            )
        );        
        
        // Write detailed pages for all of the classes
        foreach($classesList as $class)
        {
            $content = $m->render(
                file_get_contents(self::$nyansapow->getHome() . "/themes/default/templates/doxygen_class.mustache"),
                $class
            );
            
        $this->outputPage(
            $this->getDir() . $class['name'] . '.html',
            array(
                'body' => $content,
                'side-list' => $sideMenuItems,
                'page_title' => $class['name'],
                'site_name' => $this->settings['site-name'],
                'date' => date('jS F, Y H:i:s'),
                'assets_location' => \nyansapow\SiteProcessor::getAssetsLocation($this->getDir())
            )
        );               
        }
    }
}
