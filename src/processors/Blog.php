<?php
namespace nyansapow\processors;

class Blog extends \nyansapow\SiteProcessor
{
    public function outputSite()
    {
        $files = $this->getFiles();
        $structure = array();
        
        foreach($files as $file)
        {
            if(preg_match(
                "/(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})-(?<title>[a-z0-9\-\_]*)\.(md)/", 
                $file, $matches
            )){
                $structure[$matches['year']][$matches['month']][$matches['day']] = array(
                    'file' => $file,
                    'title' => $matches['title']
                );
            }
        }
    }
}
