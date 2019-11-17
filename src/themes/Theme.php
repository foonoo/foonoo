<?php


namespace nyansapow\themes;


use ntentan\utils\Filesystem;
use nyansapow\text\TemplateEngine;

class Theme
{
    private $themePath;
    private $templateEngine;

    public function __construct($themePath, TemplateEngine $templateEngine)
    {
        $this->themePath = $themePath;
        $this->templateEngine = $templateEngine;
    }

    /**
     * @param $destination
     * @throws \ntentan\utils\exceptions\FileNotFoundException
     * @throws \ntentan\utils\exceptions\FileNotReadableException
     * @throws \ntentan\utils\exceptions\FilesystemException
     */
    public function copyAssets($destination)
    {
        if(is_dir("$this->themePath/assets") && !is_dir("$destination/assets")) {
            Filesystem::directory("$this->themePath/assets")
                ->getFiles()
                ->copyTo("$destination/assets");
        }
    }

    public function renderPage($data, $layout = null)
    {
        return $this->templateEngine->render($layout ?? "layout", $data);
    }
}
