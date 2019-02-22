<?php

namespace nyansapow\processors;

use nyansapow\TextRenderer;
use ntentan\honam\TemplateEngine;

/**
 *
 */
class Wiki extends AbstractProcessor
{
    protected $pages = array();
    private $indexSet = false;
    private $toc;

    private function getPageOutput($page)
    {
        switch ($page) {
            case 'Home':
                $output = "index.html";
                $this->indexSet = true;
                break;

            default:
                $output = "{$page}.html";
                break;
        }
        return $output;
    }


    private function addPage($path)
    {
        $file = basename($path);
        if (preg_match("/^((?<chapter>[0-9]+)(--))?(?<page>.*)(\.)(?<extension>.*)/i", $file, $matches)) {
            $content = $this->readFile($file);
            $output = $this->getPageOutput($matches['page']);
            $page = array(
                'file' => $file,
                'path' => $path,
                'page' => $matches['page'],
                'extension' => $matches['extension'],
                'content' => $content,
                'output' => $output,
                'chapter' => $matches['chapter']
            );

            $this->pages[] = $page;
        }
    }

    protected function outputIndexPage()
    {
        if ($this->settings['generate_index'] ?? false) {

        }
    }

    /**
     * @throws \ntentan\honam\exceptions\FileNotFoundException
     */
    public function outputSite()
    {
        $files = $this->getFiles();

        // Filter pages from files
        foreach ($files as $path) {
            $fullPath = $this->getSourcePath($path);
            if (TextRenderer::isFileRenderable($fullPath)) {
                $this->addPage($fullPath);
            }
        }

        // Put pages into TextRenderer for link rendering
        TextRenderer::setPages($this->pages);

        // Render all pages and extract a table of contents
        foreach ($this->pages as $i => $page) {
            $content = $page['content'];
            if (isset($content['frontmatter']['toc'])) {
                $this->toc = $content['frontmatter']['toc'] === false | strtolower($content['frontmatter']['toc']) == 'off' ? false : true;
            }

            $this->pages[$i]['markedup'] = TextRenderer::render($content['body'], $page['file'], ['toc' => $this->toc]);
            $title = $content['frontmatter']['title'] ?? TextRenderer::getTitle();
            $this->pages[$i]['title'] = $title;

            if ($this->settings['mode'] ?? 'wiki' === 'book') {
                $chapter = isset($content['frontmatter']['chapter']) ? $content['frontmatter']['chapter'] : $page['chapter'];
                $this->toc[] = [
                    'chapter' => $chapter,
                    'title' => $title,
                    'url' => $page['output'],
                    'children' => TextRenderer::getTableOfContents()
                ];
            }
        }

        if ($this->settings['mode'] ?? 'wiki' === 'book') {
            usort(
                $this->toc,
                function ($a, $b) {
                    return $a['chapter'] - $b['chapter'];
                }
            );
        }

        foreach ($this->pages as $page) {
            $this->setOutputPath($page['output']);
            $this->outputWikiPage($page);
        }

        if (!$this->indexSet) {
            $this->outputIndexPage();
        }
    }

    /**
     * @param $page
     * @throws \ntentan\honam\exceptions\FileNotFoundException
     */
    protected function outputWikiPage($page)
    {
        $this->outputPage(
            TemplateEngine::render(
                'wiki',
                [
                    'book' => $this->settings['mode'] ?? 'wiki' === 'book',
                    'output' => $page['output'],
                    'toc' => $this->toc,
                    'body' => $page['markedup']
                ]
            ),
            [
                'title' => $page['title'],
                'context' => $this->settings['mode'] ?? 'wiki',
                'script' => 'wiki'
            ]
        );
    }

    protected function getDefaultTheme() {
        return 'wiki';
    }

}
