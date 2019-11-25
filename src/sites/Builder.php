<?php


namespace nyansapow\sites;


use ntentan\utils\exceptions\FileAlreadyExistsException;
use ntentan\utils\exceptions\FileNotWriteableException;
use ntentan\utils\Filesystem;
use nyansapow\text\HtmlRenderer;
use nyansapow\themes\ThemeManager;

class Builder
{
    private $themeManager;
    private $htmlRenderer;
    private $options;

    public function __construct(ThemeManager $themeManager, HtmlRenderer $htmlRenderer)
    {
        $this->themeManager = $themeManager;
        $this->htmlRenderer = $htmlRenderer;
    }

    /**
     * @param MarkupContent $page
     * @param string $sourceExtension
     * @param string $destinationExtension
     * @param array $data
     * @return string
     */
//    private function convert($page, $sourceExtension, $destinationExtension, $data)
//    {
//        if($sourceExtension != $destinationExtension) {
//            $output = $this->htmlRenderer->render($page->getBody(), $sourceExtension, $data);
//        } else {
//            $output = $page->getBody();
//        }
//        return $output;
//    }

    public function build(AbstractSite $site)
    {
        foreach($site->getPages() as $page) {
            $this->writeContentToOutputPath($site, $page);
        }
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @param AbstractSite $site
     * @param ContentInterface $page
     * @throws FileAlreadyExistsException
     * @throws FileNotWriteableException
     */
    protected function writeContentToOutputPath(AbstractSite $site, ContentInterface $page)
    {
        $destinationPath = $site->getDestinationPath($page->getDestination());
        $params = array_merge([
                'home_path' => $this->makeRelativeLocation($destinationPath, $this->options['output']),
                'site_path' => $this->makeRelativeLocation($destinationPath, $site->getDestinationPath()),
                'site_name' => $this->settings['name'] ?? '',
                'date' => date('jS F Y')
            ],
            $site->getData()
        );
        $params['body'] = $page->render($params);
        $webPage = $this->themeManager->getTheme($site)->renderPage($params);
        if (!is_dir(dirname($destinationPath))) {
            Filesystem::directory(dirname($destinationPath))->create(true);
        }
        file_put_contents($destinationPath, $webPage);
    }

    private function makeRelativeLocation($path, $relativeTo)
    {
        // Generate a relative location for the assets
        $dir = substr(preg_replace('#/+#','/', $path), strlen($relativeTo));
        $relativeLocation = '';
        if ($dir != '' && $dir != '.') {
            $dir .= substr($dir, -1) == '/' ? '' : '/';
            $relativeLocation = str_repeat('../', substr_count($dir, '/') - 1);
        }
        return $relativeLocation;
    }
}