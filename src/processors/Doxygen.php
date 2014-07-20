<?php
namespace nyansapow\processors;

class Doxygen extends \nyansapow\SiteProcessor
{
    public function outputSite() 
    {
        // Search for the index.xml file
        $indexXml = $this->getDir() . 'index.xml';
        if(!file_exists($indexXml)) return;
        $this->setLayout('doxygen.mustache', true);
                
        \nyansapow\Nyansapow::mkdir(self::$nyansapow->getDestination() . '/' . $this->getDir());
        
        // Load the index.xml file
        $index = simplexml_load_file($indexXml);
        
        // Get all the classes
        $classes = $index->xpath("/doxygenindex/compound[@kind='class']");
        $classesList = array();
        foreach($classes as $class)
        {
            $classXml = simplexml_load_file($this->getDir() . $class['refid'] . '.xml');
            $classesList[] = array(
                'name' => $class->name,
                'brief' => $classXml->compounddef->briefdescription->para
            );
        }
        
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
                'page_title' => "All Classes",
                'site_name' => $this->settings['site-name'],
                'date' => date('jS F, Y H:i:s'),
                'assets_location' => \nyansapow\SiteProcessor::getAssetsLocation($this->getDir())
            )
        );        
        
        //Write the index page lists summary of classes
        
    }
}
