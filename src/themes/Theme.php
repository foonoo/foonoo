<?php


namespace nyansapow\themes;


use ntentan\utils\exceptions\FileNotFoundException;
use ntentan\utils\exceptions\FileNotReadableException;
use ntentan\utils\exceptions\FilesystemException;
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
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FilesystemException
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
