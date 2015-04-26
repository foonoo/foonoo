<?php
namespace nyansapow;

use ntentan\honam\TemplateEngine;

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
    private $excludedPaths = array('*.', '*..', "*.gitignore", "*.git");
    
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
        
        $this->excludedPaths[] = realpath($destination);
        $this->source = realpath($source) . '/';
        $this->options = $options;
        $this->destination = $destination . '/';
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
    
    public function excluded($path)
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
        if(file_exists("{$path}site.ini"))
        {
            $sites[$path] = parse_ini_file("{$path}site.ini");
        }
        else if(!file_exists("{$path}site.ini") && $source === true)
        {
            $sites[$path] = array(
                'type' => 'site'
            );
        }
        
        while(false !== ($file = $dir->read()))
        {
            if($this->excluded("{$path}{$file}")) continue;
            if(is_dir("{$path}{$file}"))
            {
                $sites = array_merge($sites, $this->getSites("{$path}{$file}/"));
            }
        }
        
        return $sites;
    }
    
    public function write()
    {
        Processor::setup($this);
        $sites = $this->getSites($this->source, true);
        
        foreach($sites as $path => $site)
        {
            $baseDir = substr($path, strlen($this->source));
            if(is_dir("{$path}np_images"))
            {
                self::copyDir("{$path}np_images", "{$this->destination}$baseDir");
            }      
            if(is_dir("{$path}np_assets"))
            {
                self::copyDir("{$path}np_assets/*", "{$this->destination}/assets");
            }
            TemplateEngine::reset();
            $processor = Processor::get($site, $path);
            $processor->setBaseDir($baseDir);
            if(is_dir("{$path}np_data"))
            {
                $processor->setData(self::readData("{$path}np_data"));
            }
            if(is_dir("{$path}np_layouts"))
            {
                TemplateEngine::prependPath("{$path}np_layouts");
            }
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
    
    private static function readData($path)
    {
        $data = [];
        $dir = dir($path);
        $parser = new \Symfony\Component\Yaml\Parser();
        
        while(false !== ($file = $dir->read()))
        {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if($extension === 'yml' || $extension === 'yaml')
            {
                $data[pathinfo($file, PATHINFO_FILENAME)] = $parser->parse(file_get_contents("$path/$file"));
            }
        }    
        
        return $data;
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
