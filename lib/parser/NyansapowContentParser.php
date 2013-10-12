<?php
class NyansapowContentParser
{
    public static function renderTableOfContents($toc = null)
    {
        if($toc === null)
        {
            $toc = NyansapowParser::$nyansapow->getTableOfContents();
        }
        
        foreach($toc as $node)
        {    
            $output .= "<li>{$node['title']}" . self::renderTableOfContents($node['children']) . "</li>";
        }
        
        return count($toc) > 0 ? "<ul>$output </ul>" : '';
    }
}
