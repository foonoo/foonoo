<?php


namespace nyansapow\sites;


use ntentan\utils\exceptions\FileAlreadyExistsException;
use ntentan\utils\exceptions\FileNotWriteableException;
use ntentan\utils\Filesystem;
use nyansapow\content\ContentInterface;
use nyansapow\content\ThemableInterface;
use nyansapow\events\EventDispatcher;
use nyansapow\events\PageOutputGenerated;
use nyansapow\events\PagesReady;
use nyansapow\events\ThemeLoaded;
use nyansapow\text\TemplateEngine;
use nyansapow\themes\Theme;
use nyansapow\themes\ThemeManager;
use clearice\io\Io;

class SiteWriter
{
    private $themeManager;
    private $options;
    private $eventDispatcher;
    private $io;
    private $templateEngine;

    public function __construct(Io $io, ThemeManager $themeManager, EventDispatcher $eventDispatcher, TemplateEngine $templateEngine)
    {
        $this->themeManager = $themeManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->io = $io;
        $this->templateEngine = $templateEngine;
    }

    public function write(AbstractSite $site)
    {
        $theme = $this->themeManager->getTheme($site);
        $this->eventDispatcher->dispatch(ThemeLoaded::class, ['theme' => $theme]);
        $pages = $site->getPages();
        $this->eventDispatcher->dispatch(PagesReady::class, ['pages' => $pages]);

        foreach($pages as $page) {
            $this->io->output("- Writing page {$site->getDestinationPath($page->getDestination())} \n");
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

        if($layout) {
            $templateData = $site->getTemplateData($destinationPath);
            $templateData['body'] = $content->render();
            $templateData['page_title'] = $content->getMetaData()['title'] ?? "";
            if(is_a($content, ThemableInterface::class)) {
                $templateData = array_merge($templateData, $content->getLayoutData());
            }
            $output = $this->templateEngine->render($layout, $templateData);
        } else {
            $output = $content->render();
        }
        $event = $this->eventDispatcher->dispatch(PageOutputGenerated::class, ['output' => $output, 'page' => $content, 'site' => $site]);
        $output = $event ? $event->getOutput() : $output;
        if (!is_dir(dirname($destinationPath))) {
            Filesystem::directory(dirname($destinationPath))->create(true);
        }
        file_put_contents($destinationPath, $output);
    }
}
