<?php


namespace nyansapow\sites;


use ntentan\utils\exceptions\FileAlreadyExistsException;
use ntentan\utils\exceptions\FileNotWriteableException;
use ntentan\utils\Filesystem;
use nyansapow\text\TagParser;
use nyansapow\text\TemplateEngine;
use nyansapow\themes\Theme;
use nyansapow\themes\ThemeManager;

class Builder
{
    private $themeManager;
    private $templateEngine;
    private $options;

    public function __construct(ThemeManager $themeManager, TagParser $tagParser, TemplateEngine $templateEngine)
    {
        $this->themeManager = $themeManager;
        $this->tagParser = $tagParser;
        $this->templateEngine = $templateEngine;
    }

    public function build(AbstractSite $site)
    {
        $theme = $this->themeManager->getTheme($site);
        foreach($site->getPages() as $page) {
            $this->writeContentToOutputPath($site, $theme, $page);
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
    protected function writeContentToOutputPath(AbstractSite $site, Theme $theme, ContentInterface $content)
    {
        $destinationPath = $site->getDestinationPath($content->getDestination());
        $layout = $content->getMetaData()['layout'] ?? $theme->getDefaultLayoutTemplate();
        $theme->activate();

        if($layout) {
            $templateData = $site->getTemplateData($destinationPath);
            $this->tagParser->setPathToBase($templateData['site_path']);
            $templateData['body'] = $content->render();
            $templateData['page_title'] = $content->getMetaData()['title'];
            if(is_a($content, ThemableInterface::class)) {
                $templateData = array_merge($templateData, $content->getLayoutData());
            }
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
