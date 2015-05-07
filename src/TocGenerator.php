<?php

namespace nyansapow;

class TocGenerator
{
    public static $hasToc = false;
    private static $toc;
    
    private static function getTableOfContentsMarkup($toc = null)
    {
        if($toc === null)
        {
            $toc = self::$toc;
        }
        
        foreach($toc as $node)
        {    
            $output .= "<li><a href='#{$node['id']}'>{$node['title']}</a>" . self::getTableOfContentsMarkup($node['children']) . "</li>\n";
        }        
        
        return count($toc) > 0 ? "\n<ul class='toc toc-{$toc[0]['level']}'>\n$output\n </ul>\n" : '';
    }
    
    private static function getTableOfContentsTree($level = 2, $index = 0)
    {
        $tocTree = array();
        
        $xpath = new \DOMXPath(Parser::$dom);;
        $nodes = $xpath->query("//h2|//h3|//h4|//h5|//h6");
        
        for($i = $index; $i < $nodes->length; $i++)
        {
            $nodeId = str_replace(array(" ", "\t"), "-", strtolower($nodes->item($i)->nodeValue));
            $nodes->item($i)->setAttribute('id', $nodeId);
            if($nodes->item($i)->nodeName == "h{$level}")
            {
                if($nodes->item($i + 1)->nodeName == "h{$level}" || $nodes->item($i + 1) === null)
                {
                    $tocTree[] = array(
                        'id' => $nodeId,
                        'title' => $nodes->item($i)->nodeValue,
                        'level' => $level - 1,
                        'children' => array()
                    );
                }
                else if($nodes->item($i + 1)->nodeName == "h" . ($level - 1))
                {
                    $tocTree[] = array(
                        'id' => $nodeId,
                        'title' => $nodes->item($i)->nodeValue,
                        'level' => $level - 1,
                        'children' => array()
                    );
                    break;
                }
                else
                {
                    $children = self::getTableOfContentsTree($level + 1, $i + 1);
                    $newIndex = $children['index'];
                    unset($children['index']);
                    $tocTree[] = array(
                        'id' => $nodeId,
                        'title' => $nodes->item($i)->nodeValue,
                        'level' => $level - 1,
                        'children' => $children
                    );       
                    $i = $newIndex;
                }
            }
            else 
            {
                break;
            }
        }
        
        if($level > 2) $tocTree['index'] = $i;
        
        return $tocTree;        
    }
    
    public static function getTableOfContents()
    {
        return self::$toc;
    }
    
    public static function domCreated()
    {
        self::$toc = self::getTableOfContentsTree();
    }
        
    public static function renderTableOfContents()
    {
        return "<div class='toc-wrapper'><span>Contents</span>" . self::getTableOfContentsMarkup() . "</div>";
    }
}
