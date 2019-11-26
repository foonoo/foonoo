<?php


namespace nyansapow\sites;


use ntentan\utils\exceptions\FileAlreadyExistsException;
use ntentan\utils\exceptions\FileNotWriteableException;
use ntentan\utils\Filesystem;
use nyansapow\text\HtmlRenderer;
use nyansapow\text\TemplateEngine;
use nyansapow\themes\ThemeManager;

class Builder
{
    private $themeManager;
    private $htmlRenderer;
    private $templateEngine;
    private $options;

    public function __construct(ThemeManager $themeManager, HtmlRenderer $htmlRenderer, TemplateEngine $templateEngine)
    {
        $this->themeManager = $themeManager;
        $this->htmlRenderer = $htmlRenderer;
        $this->templateEngine = $templateEngine;
    }

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
     * @param ContentInterface $content
     * @throws FileAlreadyExistsException
     * @throws FileNotWriteableException
     */
    protected function writeContentToOutputPath(AbstractSite $site, ContentInterface $content)
    {
        $destinationPath = $site->getDestinationPath($content->getDestination());
        $theme = $this->themeManager->getTheme($site);
        $layout = $content->getMetaData()['layout'] ?? $theme->getDefaultLayoutTemplate();
        $theme->activate();

        if($layout) {
            $templateData = $site->getTemplateData($destinationPath);
            $templateData['body'] = $content->render();
            $output = $this->templateEngine->render($layout, $templateData);
        } else {
            $output = $content->render();
        }
        if (!is_dir(dirname($destinationPath))) {
            Filesystem::directory(dirname($destinationPath))->create(true);
        }
        file_put_contents($destinationPath, $output);
    }
}
