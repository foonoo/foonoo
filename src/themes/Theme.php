<?php


namespace nyansapow\themes;


use ntentan\utils\Filesystem;
use nyansapow\text\TemplateEngine;

class Theme
{
    private $themePath;
    private $templateEngine;
    private $templateHierachy;

    public function __construct($themePath, TemplateEngine $templateEngine, array $templateHierachy)
    {
        $this->themePath = $themePath;
        $this->templateEngine = $templateEngine;
        $this->templateHierachy = $templateHierachy;
        $this->templateHierachy[]="$themePath/templates";
    }

    /**
     * @param $destination
     * @throws \ntentan\utils\exceptions\FileNotFoundException
     * @throws \ntentan\utils\exceptions\FileNotReadableException
     * @throws \ntentan\utils\exceptions\FilesystemException
     */
    public function copyAssets($destination)
    {
        if(is_dir("$this->themePath/assets")) {
            Filesystem::directory("$this->themePath/assets")
                ->getFiles()
                ->copyTo("$destination/assets");
        }
    }

    public function activate()
    {
        $this->templateEngine->setPathHierarchy($this->templateHierachy);
    }

    public function getDefaultLayoutTemplate()
    {
        return 'layout';
    }
}
