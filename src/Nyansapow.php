<?php
namespace nyansapow;

/**
 * The Nyansapow class which represents a nyansapow site. This class performs
 * the task of converting the input files into the output site. 
 */
class Nyansapow
{
    private $options;
    private $source;
    private $destination;
    private $pages = array();
    private $home;
    private $excludedPaths = array('*.', '*..');
    
    private function __construct($source, $destination, $options)
    {
        $this->home = dirname(__DIR__);
        if($source == '')
        {
            $source = getcwd();
        }
        
        if(!file_exists($source) && !is_dir($source)) 
        {
            throw new Exception("Input directory `{$source}` does not exist or is not a directory.");
        }
        
        if($destination == '')
        {
            $destination = getcwd() . "/output_site";
        }
        
        $this->excludedPaths[] = $destination;
        $this->source = $source;
        $this->options = $options;
        $this->destination = $destination;
    }
    
    
    public function getDestination()
    {
        return $this->destination;
    }
    
    public function getSource()
    {
        return $this->source;
    }
    
    public function getHome()
    {
        return $this->home;
    }

    public static function open($source, $destination, $options = array())
    {
        return new Nyansapow($source, $destination, $options);
    }
    
    private function excluded($path)
    {   
        foreach($this->excludedPaths as $excludedPath)
        {
            if(fnmatch($excludedPath, $path))
            {
                return true;
            }
        }
        
        return false;
    }
    
    private function getSites($path, $source = false)
    {
        $sites = array();
        $dir = dir($path);
        if(file_exists("$path/site.ini"))
        {
            $sites[$path] = parse_ini_file("$path/site.ini");
        }
        else if(!file_exists("$path/site.ini") && $source === true)
        {
            $sites[$path] = array(
                'type' => 'site'
            );
        }
        
        while(false !== ($file = $dir->read()))
        {
            if($this->excluded("$path/$file")) continue;
            if(is_dir("$path/$file"))
            {
                $sites = array_merge($sites, $this->getSites("$path/$file"));
            }
        }
        
        return $sites;
    }
    
    public function write()
    {
        Processor::setup($this);
        if(is_dir("{$this->source}/images"))
        {
            self::copyDir("{$this->source}/images", "{$this->destination}");
        }
        
        $sites = $this->getSites($this->source, true);
        
        foreach($sites as $path => $site)
        {
            $processor = Processor::get($site, $path);
            $processor->setBaseDir(substr($path, strlen($this->source) + 1));
            $processor->outputSite();
        }
    }

    public static function copyDir($source, $destination)
    {
        if(!is_dir($destination) && !file_exists($destination))
        {
            self::mkdir($destination);
        }
        
        foreach(glob($source) as $file)
        {
            $newFile = (is_dir($destination) ?  "$destination/" : ''). basename("$file");
            if(is_dir($file))
            {
                self::mkdir($newFile);
                self::copyDir("$file/*", $newFile);
            }
            else
            {
                copy($file, $newFile);
            }
        }
    }

    public static function mkdir($path)
    {
        $path = explode('/', $path);
        
        foreach($path as $dir)
        {
            $dirPath .= "$dir/";
            if(!is_dir($dirPath))
            {
                mkdir($dirPath);
            }
        }
    }    
    
    public function getPages()
    {
        return $this->pages;
    }
}

class Exception extends \Exception
{
    
}
