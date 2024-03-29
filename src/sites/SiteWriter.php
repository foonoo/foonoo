<?php
namespace foonoo\sites;

use foonoo\events\AssetPipelineReady;
use foonoo\events\ContentGenerationStarted;
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
use foonoo\events\SiteWriteEnded;
use foonoo\events\ThemeLoaded;
use foonoo\text\TemplateEngine;
use foonoo\theming\Theme;
use foonoo\theming\ThemeManager;
use clearice\io\Io;

/**
 * Writes all the content of a site to its output path.
 *
 * @package foonoo\sites
 */
class SiteWriter
{
    /**
     * Instance of a theme manager.
     * @var ThemeManager
     */
    private $themeManager;

    /**
     * Instance of the event dispatcher.
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * Instance of the IO class from clearice
     * @var Io
     */
    private $io;

    /**
     * Instance of the template engine for rendering templates.
     * @var TemplateEngine
     */
    private $templateEngine;

    public function __construct(Io $io, ThemeManager $themeManager, EventDispatcher $eventDispatcher, TemplateEngine $templateEngine)
    {
        $this->themeManager = $themeManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->io = $io;
        $this->templateEngine = $templateEngine;
    }

    /**
     * Write the given site to its output path.
     *
     * @param AbstractSite $site
     * @throws FileAlreadyExistsException
     * @throws FileNotWriteableException
     */
    public function write(AbstractSite $site)
    {
        $this->themeManager->reset();
        $this->eventDispatcher->dispatch(SiteWriteStarted::class, ['site' => $site]);
        $assetPipeline = $site->getAssetPipeline();
        $theme = $this->themeManager->getTheme($site);
        $this->eventDispatcher->dispatch(ThemeLoaded::class, ['theme' => $theme]);
        if(isset($site->getMetaData()['assets'])) {
            $assetPipeline->merge($site->getMetaData()['assets'], $site->getSourcePath("_foonoo/assets"));
        }
        $this->eventDispatcher->dispatch(AssetPipelineReady::class, ['pipeline' => $assetPipeline]);
        $assetPipeline->buildAssets();
        $contents = array_map(function ($x) use ($site) {
            return $x->setSitePath($site->getDestinationPath());
        }, $site->getContent());
        $event = $this->eventDispatcher->dispatch(ContentReady::class, ['contents' => $contents]);
        $contents = $event ? $event->getPages() : $contents;
        $outputs = []; // Contains the raw output of all content

        // Render content
        /** @var Content $content */
        foreach ($contents as $i =>$content) {
            $this->io->output("- Rendering content for {$site->getDestinationPath($content->getDestination())} \n");
            $this->eventDispatcher->dispatch(ContentGenerationStarted::class, ['content' => $content]);
            $output = $content->render();
            /** @var ContentOutputGenerated $event */
            $event = $this->eventDispatcher->dispatch(
                    ContentOutputGenerated::class, ['output' => $output, 'content' => $content, 'site' => $site]
                );
            $outputs[$i] = $event !== null ? $event->getOutput() : $output;
        }

        $this->eventDispatcher->dispatch(AllContentsRendered::class, ['site' => $site]);

        foreach($contents as $i => $content) {
            $this->io->output("- Writing content to {$site->getDestinationPath($content->getDestination())} \n");
            $this->writeContentToOutputPath($site, $theme, $outputs[$i], $content);
        }
        $this->eventDispatcher->dispatch(SiteWriteEnded::class, ['site' => $site]);
    }

    /**
     * @param AbstractSite $site
     * @param Theme $theme
     * @param Content|ThemableInterface $content
     * @throws FileAlreadyExistsException
     * @throws FileNotWriteableException
     */
    private function writeContentToOutputPath(AbstractSite $site, Theme $theme, string $output, Content|ThemableInterface $content)
    {
        $destinationPath = $site->getDestinationPath($content->getDestination());
        $layout = $content->getMetaData()['frontmatter']['layout'] ?? $theme->getDefaultLayoutTemplate();

        if ($layout) {
            $templateData = array_merge($site->getTemplateData($content), $content->getMetaData());
            $templateData['body'] = $output;
            $templateData['content_title'] = $content->getMetaData()['frontmatter']['title'] ?? $content->getMetaData()['title'] ?? "";
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

