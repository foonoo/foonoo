<?php


namespace nyansapow\sites;


use ntentan\utils\Filesystem;
use nyansapow\text\HtmlRenderer;
use nyansapow\text\TemplateEngine;
use nyansapow\themes\ThemeManager;

class Builder
{
    private $themeManager;
    private $htmlRenderer;

    public function __construct(ThemeManager $themeManager, HtmlRenderer $htmlRenderer)
    {
        $this->themeManager = $themeManager;
        $this->htmlRenderer = $htmlRenderer;
    }

    private function convert($page, $sourceExtension, $destinationExtension)
    {
        if($sourceExtension != $destinationExtension) {
            $output = $this->htmlRenderer->render($page->getBody(), $sourceExtension);
        } else {
            $output = $page->getBody();
        }
        return $output;
    }

    public function build(AbstractSite $site, array $data)
    {
        foreach($site->getPages() as $page) {
            $this->writeContentToOutputPath($site, $page, $data);
        }
    }

    /**
     * @param $site
     * @param $content
     * @param $path
     * @param array $overrides
     * @throws \ntentan\utils\exceptions\FileAlreadyExistsException
     * @throws \ntentan\utils\exceptions\FileNotWriteableException
     */
    protected function writeContentToOutputPath(AbstractSite $site, Page $page, array $overrides = array())
    {
        $sourcePath = $page->getSource();
        $destinationPath = $page->getDestination();
        $params = array_merge([
                'body' => $this->convert($page, $sourcePath, $destinationPath),
                'home_path' => $this->makeRelativeLocation($site->getSourcePathRelativeToRoot() . $destinationPath),
                'site_path' => $this->makeRelativeLocation($destinationPath),
                'site_name' => $this->settings['name'] ?? '',
                'date' => date('jS F Y')
            ],
            $overrides
        );
        $outputPath = $site->getSourcePath() . $destinationPath;
        $webPage = $this->themeManager->getTheme($site->getDefaultTheme(), $site->getSourcePath(), $site->getDestinationPath())->renderPage($params);
        if (!is_dir(dirname($outputPath))) {
            Filesystem::directory(dirname($outputPath))->create(true);
        }
        file_put_contents($outputPath, $webPage);
    }

    private function makeRelativeLocation($dir)
    {
        // Generate a relative location for the assets
        $relativeLocation = '';
        if ($dir != '' && $dir != '.') {
            $dir .= substr($dir, -1) == '/' ? '' : '/';
            $relativeLocation = str_repeat('../', substr_count($dir, '/') - 1);
        }
        return $relativeLocation;
    }

//    /**
//     * Returns the relative path to the site directory.
//     *
//     * @param string $path
//     * @param AbstractSite $site
//     * @return string
//     */
//    protected function getRelativeSitePath(string $path)
//    {
//        return $this->getRelativeBaseLocation($path);
//    }
//
//    /**
//     * Returns the relative path to the base directory of all sites when using multiple sites.
//     *
//     * @param string $path
//     * @param AbstractSite $site
//     * @return string
//     */
//    protected function getRelativeHomePath(string $path)
//    {
//        return $this->getRelativeBaseLocation($site->getSourcePathRelativeToRoot() . $path);
//    }

}