<?php
namespace nyansapow;

abstract class SiteProcessor
{
    private $files = array();
    protected $settings;
    
    /**
     *
     * @var \nyansapow\Nyansapow
     */
    protected static $nyansapow;
    
    private function __construct($settings = array())
    {
        $this->settings = $settings;
    }
    
    public static function init($nyasapow)
    {
        self::$nyansapow = $nyasapow;
        return new processors\Wiki();
    }
    
    public static function get($settings)
    {
        $siteType = $settings['site-type'] == '' ? 'wiki' : $settings['site-type'];
        $class = "\\nyansapow\\processors\\" . ucfirst($siteType);
        return new $class($settings);
    }
    
    public function addFile($file)
    {
        $this->files[] = $file;
    }
    
    protected function getFiles()
    {
        return $this->files;
    }
    
    protected function outputPage()
    {
        
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
        if($dir != '')
        {
            $dir .= '/';
            $assetsLocation = str_repeat('../', substr_count($dir, '/'));
        }        
        return $assetsLocation;
    }
    
    public abstract function outputSite();
}
