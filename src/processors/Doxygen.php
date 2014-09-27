<?php
namespace nyansapow\processors;

class Doxygen extends \nyansapow\SiteProcessor
{
    private $paragraphMode = true;
    private $noRefs = false;
    
    public function parseSimpleSect($node, $simpleNode)
    {
        $previousParagraphMode = $this->paragraphMode;
        $this->paragraphMode = false;
        switch($simpleNode['kind'])
        {
            case 'author':
                $html .= '<dl>';
                $html .= '<dt>Author</dt>';
                $html .= '<dd>' . $this->parseText($node) . '</dd>';
                $html .= '</dl>';
                break;
            
            case 'see':
                $html .= '<dl>';
                $html .= '<dt>See also</dt>';
                $html .= '<dd>' . $this->parseText($node) . '</dd>';
                $html .= '</dl>';                
                break;
            
            case 'warning':
                $html .= '<div class="block warning">' . $this->parseText($node) . '</div>';
                break;
            
            case 'return':
                $html .= '<dl>';
                $html .= '<dt>Returns</dt>';
                $html .= '<dd>' . $this->parseText($node) . '</dd>';
                $html .= '</dl>';                
                break;
            
            default:
                echo "Unknown simplenode kind: '{$simpleNode['kind']}'\n";
                $html .= $this->parseText($node);
                break;
        }
        $this->paragraphMode = $previousParagraphMode;
        
        return $html;
    }
    
    public function parsePara($node)
    {
        $html .= $this->parseText($node);
        return $html;
    }
    
    public function parseRef($node)
    {
        if($this->noRefs) 
        {
            return $this->parseText($node);
        }
        else
        {
            $class = $this->parseText($node);
            return "<a href='$class.html'>$class</a>";
        }
    }
    
    public function parseUlink($node, $simpleNode)
    {
        return "<a href='{$simpleNode['url']}'>" . $this->parseText($node) . "</a>";
    }
    
    public function parseItemizedlist($node)
    {
        return '<ol>' . $this->parseText($node) . '</ol>';
    }
    
    public function parseListitem($node)
    {
        return '<li>' . $this->parseText($node) . '</li>';
    }
    
    public function parseProgramlisting($node)
    {
        return '<pre><code>' . $this->parseText($node) . '</code></pre>';
    }
    
    public function parseXrefsect($node, $simple)
    {
        
        $title = $simple->xreftitle;
        return "<div class='block'><div><b>{$title}</b></div>" . $this->parseText(dom_import_simplexml($simple->xrefdescription)) . "</div>";
    }
    
    public function parseParameterlist($node, $simple)
    {
        $html = '<dl><dt>Parameters</dt><dd><table>';
        foreach($simple->parameteritem as $param)
        {
            $description = $this->getMarkup($param->parameterdescription);
            $html .= "<tr><td>{$param->parameternamelist->parametername}</td><td>$description</td></tr>";
        }
        $html .= '</table></dd></dl>';
        return $html;
    }
    
    public function parseElementNode($node)
    {
        $simpleNode = simplexml_import_dom($node);
        
        try {
            $method = new \ReflectionMethod($this, "parse" . ucfirst($node->nodeName));
            $html .= $method->invoke($this, $node, $simpleNode);
        } catch (\Exception $ex) {
            print "Unkown node type {$node->nodeName}\n";
            $html .= $this->parseText($node);
        }     
        
        return $html;
    }
    
    public function parseSp()
    {
        return ' ';
    }
    
    protected function parseText($textNodes)
    {
        foreach($textNodes->childNodes as $node)
        {
            switch($node->nodeType){
                case XML_ELEMENT_NODE:
                    $html .= $this->parseElementNode($node);
                    break;
                case XML_TEXT_NODE:
                    $html .= $node->textContent;
                    break;
            }
        }
        
        return $html;
    }
    
    protected function getMarkup($node)
    {
        return $this->parseText(dom_import_simplexml($node));
    }
    
    private function extractFieldDetails($label, $fields)
    {
        $fieldDetails = array();
        
        foreach($fields as $field)
        {
            $fieldDetails[] = array(
                'name' => (string)$field->name,
                'brief' => $this->getMarkup($field->briefdescription),
                'description' => $this->getMarkup($field->detaileddescription),
                'initializer' => (string)$field->initializer
            );
        }     
        
        if(count($fields) == 0)
        {
            return null;
        }
        else
        {
            return array(
                'label' => $label,
                'items' => $fieldDetails
            );
        }
    }
    
    private function extractMethodDetails($label, $methods)
    {
        $methodDetails = array();
        
        foreach($methods as $method)
        {
            $params = array();

            foreach($method->param as $param)
            {
                $params = array(
                    'name' => $param->name
                );
            }

            $methodDetails[] = array(
                'name' => (string)$method->name,
                'brief' => $this->getMarkup($method->briefdescription),
                'description' => $this->getMarkup($method->detaileddescription),
                'params' => $params,
                'args_string' => (string)$method->argsstring
            );
        } 
        
        if(count($methods) == 0)
        {
            return null;
        }
        else
        {
            return array(
                'label' => $label,
                'items' => $methodDetails
            );
        }
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
            //print "###{$class->name}\n";
            $classDescription = array(
                'name' => (string)$class->name,
                'link' => (string)$class->name . ".html",
                'brief' => $this->getMarkup($classXml->compounddef->briefdescription),
                'description' => $this->getMarkup($classXml->compounddef->detaileddescription),
                'access' => (string)$classXml->compounddef['prot'],
                'parents' => array(),
                'sections' => array(
                    $this->extractFieldDetails('Constants', $classXml->xpath("//memberdef[type='const']")),
                    $this->extractFieldDetails('Static Public Fields', $classXml->xpath("//memberdef[@kind='variable' and @static='yes' and @prot='public']")),
                    $this->extractFieldDetails('Public Fields', $classXml->xpath("//memberdef[@kind='variable' and @static='no' and @prot='public' and type!='const']")),
                    $this->extractMethodDetails('Static Public Methods', $classXml->xpath("//memberdef[@kind='function' and @static='yes' and @prot='public']")),
                    $this->extractMethodDetails('Public Methods', $classXml->xpath("//memberdef[@kind='function' and @static='no' and @prot='public']")),
                    $this->extractFieldDetails('Static Protected Fields', $classXml->xpath("//memberdef[@kind='variable' and @static='yes' and @prot='protected']")),
                    $this->extractFieldDetails('Protected Fields', $classXml->xpath("//memberdef[@kind='variable' and @static='no' and @prot='protected' and type!='const']")),
                    $this->extractMethodDetails('Static Protected Methods', $classXml->xpath("//memberdef[@kind='function' and @static='yes' and @prot='protected']")),
                    $this->extractMethodDetails('Protected Methods', $classXml->xpath("//memberdef[@kind='function' and @static='no' and @prot='protected']"))
                )
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
        $m = new \Mustache_Engine(array(
            'partials_loader' => new \Mustache_Loader_FilesystemLoader(self::$nyansapow->getHome() . "/themes/default/templates")
        ));
        
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
                    'assets_location' => \nyansapow\SiteProcessor::getAssetsLocation($this->getDir()),
                    'sections' => $class['sections']
                )
            );               
        }
    }
}
