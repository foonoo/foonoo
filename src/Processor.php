<?php
namespace nyansapow;

abstract class Processor
{
    protected $settings;
    private $dir;
    private $layout;
    private $baseDir;
    private $theme;
    protected $templates;
    private $outputPath;
    
    /**
     * 
     * @var \nyansapow\Nyansapow
     */
    protected static $nyansapow;
    
    /**
     *
     * @var \Mustache_Engine
     */
    protected $mustache;
    
    private function __construct($settings = array(), $dir = '')
    {
        $this->dir = $dir;
        $this->settings = $settings;
        $this->mustache = new \Mustache_Engine();
        
        if(isset($settings['layout']))
        {
            $this->setLayout($settings['layout']);
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
    
    public function setTheme($theme, $overwrite = false)
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
        
        Nyansapow::copyDir("$theme/assets/*", self::$nyansapow->getDestination() . "/assets");  
        $this->templates = "$theme/templates";
        $this->mustache->setLoader(new \Mustache_Loader_FilesystemLoader($this->templates));
                
        if($this->layout == '' && file_exists("$theme/templates/layout.mustache"))
        {
            $this->layout = "layout";
        }
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
        Parser::setBaseDir($baseDir);
    }
    
    protected function setLayout($layout, $core = false)
    {
        $this->layout = ($core === true ? self::$nyansapow->getHome() . "/themes/default/templates/" : '') . $layout;
    }
    
    protected function getFiles($base = '', $recursive = false)
    {
        $files = array();
        $dir = dir($this->dir . '/' . $base);
        while(false !== ($file = $dir->read()))
        {
            $path = "{$this->dir}/$base/$file";
            if(is_dir($path) && $recursive)
            {
                $files = array_merge($files, $this->getFiles($path, true));
            }
            else if(!is_dir($path))
            {
                $files[] = $path;
            }
        }
        return $files;
    }
    
    protected function outputPage($content, $overrides = array())
    {       
        $params = array_merge(
            array(
                'body' => $content,
                'home_path' => $this->getAssetsLocation("{$this->baseDir}/{$this->outputPath}"),
                'site_path' => $this->getAssetsLocation($this->outputPath),
                'site_name' => $this->settings['name'],
                'date' => date('jS F Y')
            ), 
            $overrides
        );
        $webPage = $this->mustache->render($this->layout, $params);
        self::writeFile(self::$nyansapow->getDestination() . "/{$this->baseDir}" . ($this->outputPath[0] == '/' ? '' : '/') . $this->outputPath, $webPage);
    }
    
    protected static function writeFile($path, $contents)
    {
        if(!is_dir(dirname($path))) 
        {
            Nyansapow::mkdir (dirname($path));
        }
        
        file_put_contents($path, $contents);
    }
    
    protected function getAssetsLocation($dir)
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
        $this->outputPath = $path;
        Parser::setPathToBase($this->getAssetsLocation($path));
    }
    
    public abstract function outputSite();
}
