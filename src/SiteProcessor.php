<?php
namespace nyansapow;

abstract class SiteProcessor
{
    private $files = array();
    protected $settings;
    private $dir;
    private $layout;
    
    /**
     * 
     * @var \nyansapow\Nyansapow
     */
    protected static $nyansapow;
    private $m;
    
    private function __construct($settings = array(), $dir = '')
    {
        $this->dir = $dir;
        $this->settings = $settings;
        $this->m = new \Mustache_Engine();
        $this->setLayout('layout.mustache', true);
    }
    
    public static function init($nyasapow)
    {
        self::$nyansapow = $nyasapow;
        return new processors\Wiki();
    }
    
    public static function get($settings, $dir)
    {
        $siteType = $settings['site-type'] == '' ? 'wiki' : $settings['site-type'];
        $class = "\\nyansapow\\processors\\" . ucfirst($siteType);
        return new $class($settings, $dir);
    }
    
    public function addFile($file)
    {
        $this->files[] = $file;
    }
    
    public function getDir()
    {
        return $this->dir;
    }
    
    protected function setLayout($layout, $core = false)
    {
        $this->layout = ($core === true ? self::$nyansapow->getHome() . "/themes/default/templates/" : '') . $layout;
    }
    
    protected function getFiles()
    {
        return $this->files;
    }
    
    protected function outputPage($file, $params)
    {
        $webPage = $this->m->render(file_get_contents($this->layout), $params);
        self::writeFile(self::$nyansapow->getDestination() . ($file[0] == '/' ? '' : '/') . $file, $webPage);            
    }
    
    protected static function writeFile($path, $contents)
    {
        if(!is_dir(dirname($path))) 
        {
            Nyansapow::mkdir (dirname($path));
        }
        
        file_put_contents($path, $contents);
    }
    
    protected static function getAssetsLocation($dir)
    {
        // Generate a relative location for the assets
        $assetsLocation = '';
        if($dir != '' && $dir != '.')
        {
            $dir .= substr($dir, -1) == '/' ? '' : '/';
            $assetsLocation = str_repeat('../', substr_count($dir, '/'));
        }        
        return $assetsLocation;
    }
    
    public abstract function outputSite();
}
