<?php

namespace nyansapow\processors;

use ntentan\honam\TemplateEngine;
use nyansapow\TextRenderer;

class Blog extends AbstractProcessor
{
    private $posts = [];
    private $archives = [];
    private $tags = [];

    private function splitPost($post)
    {
        $previewRead = false;
        $lines = explode("\n", $post);
        $preview = '';
        $body = '';
        $continuation = '';
        $moreLink = false;

        foreach ($lines as $line) {
            if (preg_match("/(?<preview>.*)(?<tag>\<\!\-\-\s*more\s*\-\-\>)(?<line>.*)/i", $line, $matches)) {
                $preview .= "{$matches['preview']}\n";
                $body .= "{$matches['preview']} {$matches['line']}\n";
                $previewRead = true;
                $moreLink = true;
                $continuation .= "{$matches['line']}\n";
                continue;
            }
            if (!$previewRead) {
                $preview .= "$line\n";
            } else {
                $continuation .= "$line\n";
            }
            $body .= "$line\n";
        }

        return ['post' => $body, 'preview' => $preview, 'more_link' => $moreLink, "continuation" => $continuation];
    }

    protected function getFiles($base = '', $recursive = false)
    {
        $files = parent::getFiles($base, $recursive);
        rsort($files);
        return $files;
    }

    private function preProcessFiles($files)
    {
        foreach ($files as $file) {
            if (preg_match("/(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})-(?<title>[A-Za-z0-9\-\_]*)\.(md)/",$file, $matches)) {
                $post = $this->readFile($file);
                $post['frontmatter']['title'] = $post['frontmatter']['title'] ?? ucfirst(str_replace("-", " ", $matches['title']));
                $splitPost = $this->splitPost($post['body']);
                $this->posts[] = array(
                    'body' => $splitPost['post'],
                    'title' => $post['frontmatter']['title'],
                    'date' => date("jS F Y", strtotime("{$matches['year']}-{$matches['month']}-{$matches['day']}")),
                    'preview' => $splitPost['preview'],
                    'preview_text' => $splitPost['preview'],
                    'continuation' => $splitPost['continuation'],
                    'path' => "{$matches['year']}/{$matches['month']}/{$matches['day']}/{$matches['title']}.html",
                    'category' => $post['frontmatter']['category'] ?? null,
                    'frontmatter' => $post['frontmatter'],
                    'info' => $matches,
                    'more_link' => $splitPost['more_link'],
                    'file' => $file,
                    'author' => $post['frontmatter']['author'] ?? $this->settings['author']
                );
                $this->archives[$matches['year']]['posts'] = array();
                $this->archives[$matches['year']][$matches['month']]['posts'] = array();
                $this->archives[$matches['year']][$matches['month']][$matches['day']]['posts'] = array();
            }
        }
    }

    private function writePosts()
    {
        foreach ($this->posts as $i => $post) {
            $this->setOutputPath($post['path']);
            $this->posts[$i]['body'] = TextRenderer::render($post['body'], $post['file']);
            $this->posts[$i]['preview'] = TextRenderer::render($post['preview'], $post['file']);

            $markedup = TemplateEngine::render(
                'post',
                array_merge(
                    $this->posts[$i],
                    [
                        'next' => isset($this->posts[$i - 1]) ? $this->posts[$i - 1] : false,
                        'prev' => isset($this->posts[$i + 1]) ? $this->posts[$i + 1] : false,
                    ]
                )
            );

            $this->outputPage($markedup, ['page_title' => $post['frontmatter']['title']]);

            $this->archives[$post['info']['year']]['posts'][] = $i;
            $this->archives[$post['info']['year']]['months'][$post['info']['month']]['posts'][] = $i;
            $this->archives[$post['info']['year']]['months'][$post['info']['month']]['days'][$post['info']['day']]['posts'][] = $i;

            foreach ($post['frontmater'] ['tags'] ?? [] as $tag) {
                $this->tags[trim($tag)][] = $i;
            }
        }
    }

    public function outputSite()
    {
        $files = $this->getFiles("posts");
        $categories = array();

        $this->preProcessFiles($files);
        $this->writePosts();
        $this->writeIndex('index.html');
        $this->writeArchive($this->archives, ['months', 'days'], 'years');
        $this->writeFeed();

        // Write categories

        // Write tags

    }

    private function formatValue($stage, $value)
    {
        switch ($stage) {
            case 'years':
                return $value;
            case 'months':
                return $this->getMonthName($value);
            case 'days':
                return $this->rank($value);
        }
    }

    private function rank($number)
    {
        switch (substr($number, -1)) {
            case 1:
                return "{$number}st";
            case 2:
                return "{$number}nd";
            case 3:
                return "{$number}rd";
            default:
                return "{$number}th";
        }
    }

    private function getMonthName($number)
    {
        switch ($number) {
            case  1:
                return 'January';
            case  2:
                return 'February';
            case  3:
                return 'March';
            case  4:
                return 'April';
            case  5:
                return 'May';
            case  6:
                return 'June';
            case  7:
                return 'July';
            case  8:
                return 'August';
            case  9:
                return 'September';
            case 10:
                return 'October';
            case 11:
                return 'November';
            case 12:
                return 'December';
        }
    }

    private function writeArchive($archive, $order = array(), $stage = null, $title = 'Archive', $baseUrl = '')
    {
        $nextStage = array_shift($order);

        foreach ($archive as $value => $posts) {
            $newTitle = $this->formatValue($stage, $value) . " $title";
            $newBaseUrl = "$baseUrl$value/";
            $this->writeIndex("{$newBaseUrl}index.html", $posts['posts'], $newTitle);
            if ($nextStage != null) {
                $this->writeArchive($posts[$nextStage], $order, $nextStage, $newTitle, $newBaseUrl);
            }
        }
    }

    private function writeIndex($target, $posts = array(), $title = null)
    {
        if (count($posts)) {
            $rebuiltPosts = array();
            foreach ($posts as $post) {
                $rebuiltPosts[] = $this->posts[$post];
            }
        } else {
            $rebuiltPosts = $this->posts;
        }

        $body = TemplateEngine::render(
            'listing',
            array(
                'listing_title' => $title,
                'previews' => true,
                'posts' => $rebuiltPosts,
                'path_to_base' => $this->getRelativeBaseLocation($target)
            )
        );
        $this->setOutputPath($target);
        $this->outputPage($body);
    }

    private function writeFeed()
    {
        $feed = TemplateEngine::render("feed",
            array(
                'posts' => $this->posts,
                'title' => $this->settings['name'] ?? 'Untitled Blog',
                'description' => $this->settings['description'] ?? '',
                'url' => $this->settings['url'] ?? ''
            )
        );
        $this->setOutputPath("feed.xml");
        $this->setLayout('plain');
        $this->outputPage($feed);
    }

    protected function getDefaultTheme() {
        return 'blog';
    }
}
