<?php
namespace nyansapow;

use ntentan\honam\TemplateEngine;
use ntentan\honam\AssetsLoader;

abstract class Processor
{
    protected $settings;
    private $dir;
    private $layout;
    protected $baseDir;
    private $theme;
    protected $templates;
    private $outputPath;
    protected $data;
    
    /**
     * 
     * @var \nyansapow\Nyansapow
     */
    protected static $nyansapow;
    
    private function __construct($settings = array(), $dir = '')
    {
        $this->dir = $dir;
        $this->settings = $settings;
        
        if(isset($settings['layout']))
        {
            $this->setLayout($settings['layout']);
        }
        else
        {
            $this->setLayout('layout');
        }
        
        if(isset($settings['theme']))
        {
            $this->setTheme($settings['theme']);
        }
        
        $this->init();
    }
    
    public function init()
    {
        
    }
    
    public function setTheme($theme)
    {
        $this->theme = $theme;
        if(!file_exists("{$this->dir}/{$theme}"))
        {
            $theme = self::$nyansapow->getHome() . "/themes/{$theme}";
        }
        else
        {
            $theme = "{$this->dir}/{$theme}";
        }
                
        Nyansapow::copyDir("$theme/copy/*", self::$nyansapow->getDestination() . "/assets");  
        TemplateEngine::prependPath("$theme/templates");
        AssetsLoader::prependSourceDir("$theme/assets");
    }
    
    public static function setup($nyasapow)
    {
        self::$nyansapow = $nyasapow;
    }
    
    public static function get($settings, $dir)
    {
        $class = "\\nyansapow\\processors\\" . ucfirst($settings['type']);
        return new $class($settings, $dir);
    }
    
    public function getDir()
    {
        return $this->dir;
    }
    
    public function setBaseDir($baseDir)
    {
        $this->baseDir = $baseDir;
    }
    
    protected function setLayout($layout)
    {
        $this->layout = $layout;
    }
    
    protected function getFiles($base = '', $recursive = false)
    {
        $files = array();
        $dir = scandir("{$this->dir}/$base", SCANDIR_SORT_ASCENDING);
        foreach($dir as $file)
        {
            $path = "{$this->dir}" . ($base == '' ? '' : "$base/") . "$file";
            if(self::$nyansapow->excluded($path)) continue;
            if(is_dir($path) && $recursive)
            {
                $files = array_merge($files, $this->getFiles($path, true));
            }
            else if(!is_dir($path))
            {
                $path = substr($path, strlen(realpath(self::$nyansapow->getSource() . $this->baseDir)));
                $files[] = $path;
            }
        }
        return $files;
    }
    
    protected function getSitePath()
    {
        return $this->getRelativeBaseLocation($this->outputPath);
    }
    
    protected function getHomePath()
    {
        return $this->getRelativeBaseLocation($this->baseDir . $this->outputPath);
    }
    
    protected function outputPage($content, $overrides = array())
    {       
        $params = array_merge(
            array(
                'body' => $content,
                'home_path' => $this->getHomePath(),
                'site_path' => $this->getSitePath(),
                'site_name' => $this->settings['name'],
                'date' => date('jS F Y')
            ), 
            $overrides
        );
        $webPage = TemplateEngine::render($this->layout, $params);
        self::writeFile($this->getDestinationPath($this->outputPath), $webPage);
    }
    
    protected static function writeFile($path, $contents)
    {
        if(!is_dir(dirname($path))) 
        {
            Nyansapow::mkdir (dirname($path));
        }
        file_put_contents($path, $contents);
    }
    
    protected function getRelativeBaseLocation($dir)
    {
        // Generate a relative location for the assets
        $assetsLocation = '';
        if($dir != '' && $dir != '.')
        {
            $dir .= substr($dir, -1) == '/' ? '' : '/';
            $assetsLocation = str_repeat('../', substr_count($dir, '/') - 1);
        }        
        return $assetsLocation;
    }
    
    public function setOutputPath($path)
    {
        if($path[0] == '/')
        {
            $path = substr($path, 1);
        }
        $this->outputPath = $path;
        Parser::setPathToBase($this->getRelativeBaseLocation($path));
        AssetsLoader::setSiteUrl($this->getRelativeBaseLocation("{$this->baseDir}{$this->outputPath}") . "assets");
    }
    
    protected function getSourcePath($path)
    {
        return realpath(self::$nyansapow->getSource() . $this->baseDir) . "/" . $path;
    }
    
    protected function getDestinationPath($path)
    {
        return self::$nyansapow->getDestination() . $this->baseDir . $path;
    }   
    
    protected function readFile($textFile)
    {
        $file = fopen($this->getSourcePath($textFile), 'r');
        $frontmatterRead = false;
        $postStarted = false;
        $body = '';
        $frontmatter = '';
        
        while(!feof($file))
        {
            $line = fgets($file);
            if(!$frontmatterRead && !$postStarted && (trim($line) === '<<<<' || trim($line) === '<<<'))
            {
                $frontmatter = $this->readFrontMatter($file);
                $frontmatterRead = true;
                continue;
            }
            $postStarted = true;
            $body .= $line;
        }
        
        return array(
            'body' => $body,
            'frontmatter' => $frontmatter
        );
    }
    
    private function readFrontMatter($file)
    {
        $frontmatter = '';
        do
        {
            $line = fgets($file);
            if(trim($line) === '>>>>' || trim($line) === '>>>') break;
            $frontmatter .= $line;
        }
        while(!feof($file));
        
        return parse_ini_string($frontmatter, true);
    }  
    
    public function setData($data)
    {
        $this->data = $data;
    }
    
    public abstract function outputSite();
}
