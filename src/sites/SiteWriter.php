<?php
namespace foonoo\sites;

use foonoo\events\AssetPipelineReady;
use foonoo\events\ContentLayoutApplied;
use foonoo\events\AllContentsRendered;
use ntentan\utils\exceptions\FileAlreadyExistsException;
use ntentan\utils\exceptions\FileNotWriteableException;
use ntentan\utils\Filesystem;
use foonoo\content\Content;
use foonoo\content\ThemableInterface;
use foonoo\events\EventDispatcher;
use foonoo\events\ContentOutputGenerated;
use foonoo\events\ContentReady;
use foonoo\events\ContentWritten;
use foonoo\events\SiteWriteStarted;
use foonoo\events\ThemeLoaded;
use foonoo\text\TemplateEngine;
use foonoo\themes\Theme;
use foonoo\themes\ThemeManager;
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

    /**
     * Write a site.
     *
     * @param AbstractSite $site
     * @throws FileAlreadyExistsException
     * @throws FileNotWriteableException
     */
    public function write(AbstractSite $site)
    {
        $this->eventDispatcher->dispatch(SiteWriteStarted::class, ['site' => $site]);
        $theme = $this->themeManager->getTheme($site);
        $this->eventDispatcher->dispatch(ThemeLoaded::class, ['theme' => $theme]);
        $assetPipeline = $site->getAssetPipeline();
        $this->eventDispatcher->dispatch(AssetPipelineReady::class, ['pipeline' => $assetPipeline]);
        $assetPipeline->buildAssets();
        $contents = array_map(function ($x) use ($site) {
            return $x->setSitePath($site->getDestinationPath());
        }, $site->getContent());
        $event = $this->eventDispatcher->dispatch(ContentReady::class, ['contents' => $contents]);
        $contents = $event ? $event->getPages() : $contents;
        $outputs = [];

        // Render content
        /** @var Content $content */
        foreach ($contents as $i =>$content) {
            $output = $content->render();
            /** @var ContentOutputGenerated $event */
            $event = $this->eventDispatcher->dispatch(ContentOutputGenerated::class, ['output' => $output, 'content' => $content, 'site' => $site]);
            $outputs[$i] = $event ? $event->getOutput() : $output;
        }

        $this->eventDispatcher->dispatch(AllContentsRendered::class, ['site' => $site]);

        foreach($contents as $i => $content) {
            $this->writeContentToOutputPath($site, $theme, $outputs[$i], $content);
        }
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @param AbstractSite $site
     * @param Theme $theme
     * @param Content $content
     * @throws FileAlreadyExistsException
     * @throws FileNotWriteableException
     */
    private function writeContentToOutputPath(AbstractSite $site, Theme $theme, string $output, Content $content)
    {
        $destinationPath = $site->getDestinationPath($content->getDestination());
        $layout = $content->getMetaData()['layout'] ?? $theme->getDefaultLayoutTemplate();

        if ($layout) {
            $templateData = array_merge($site->getTemplateData($destinationPath), $content->getMetaData());
            $templateData['body'] = $output;
            $templateData['content_title'] = $content->getMetaData()['title'] ?? "";
            if (is_a($content, ThemableInterface::class)) {
                $templateData = array_merge($templateData, $content->getLayoutData());
            }
            $finalOutput = $this->templateEngine->render($layout, $templateData);
            $event = $this->eventDispatcher->dispatch(ContentLayoutApplied::class, ['output' => $finalOutput, 'content' => $content, 'site' => $site]);
            $finalOutput = $event ? $event->getOutput() : $finalOutput;
        } else {
            $finalOutput = $content->render();
        }
        if (!is_dir(dirname($destinationPath))) {
            Filesystem::directory(dirname($destinationPath))->create(true);
        }
        file_put_contents($destinationPath, $finalOutput);
        $this->eventDispatcher->dispatch(ContentWritten::class, ['content' => $content, 'destination_path' => $destinationPath]);
    }
}
