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
    
    /**
     * 
     * @var \nyansapow\Nyansapow
     */
    protected static $nyansapow;
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
        
        if($this->layout == '' && file_exists("$theme/templates/layout.mustache"))
        {
            $this->layout = "$theme/templates/layout.mustache";
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
    
    protected function outputPage($file, $content, $overrides = array())
    {
        $params = array_merge(
            array(
                'body' => $content,
                'assets_location' => $this->getAssetsLocation("{$this->baseDir}/{$file}"),
                'site_name' => $this->settings['name']
            ), 
            $overrides
        );
        $webPage = $this->mustache->render(file_get_contents($this->layout), $params);
        self::writeFile(self::$nyansapow->getDestination() . "/{$this->baseDir}" . ($file[0] == '/' ? '' : '/') . $file, $webPage);            
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
    
    public abstract function outputSite();
}
