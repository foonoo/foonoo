<?php

namespace nyansapow\generators;

use ntentan\honam\TemplateEngine;
use nyansapow\TextRenderer;

class BlogGenerator extends AbstractGenerator
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
                    'body_text' => $splitPost['post'],
                    'title' => $post['frontmatter']['title'],
                    'date' => date("jS F Y", strtotime("{$matches['year']}-{$matches['month']}-{$matches['day']}")),
                    'preview_text' => $splitPost['preview'],
                    'continuation' => $splitPost['continuation'],
                    'path' => "{$matches['year']}/{$matches['month']}/{$matches['day']}/{$matches['title']}.html",
                    'category' => $post['frontmatter']['category'] ?? null,
                    'frontmatter' => $post['frontmatter'],
                    'info' => $matches,
                    'more_link' => $splitPost['more_link'],
                    'file' => $file,
                    'format' => pathinfo($file, PATHINFO_EXTENSION),
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
            $this->posts[$i]['body'] = $this->textProcessors->renderHtml($post['body_text'], $post['format']);
            $this->posts[$i]['preview'] = $this->textProcessors->renderHtml($post['preview_text'], $post['format']);
            $this->posts[$i]['home_path'] = $this->getRelativeHomePath();
            $this->posts[$i]['site_path'] = $this->getRelativeSitePath();

            $markedup = $this->templateEngine->render(
                'post',
                array_merge(
                    $this->posts[$i],
                    [
                        'next' => isset($this->posts[$i - 1]) ? $this->posts[$i - 1] : false,
                        'prev' => isset($this->posts[$i + 1]) ? $this->posts[$i + 1] : false,
                    ]
                )
            );

            $this->writeContentToOutputPath($markedup, 
                ['page_title' => $post['frontmatter']['title'], 'page_type' => 'post', 'frontmatter' => $post['frontmatter']]
            );

            $this->archives[$post['info']['year']]['posts'][] = $i;
            $this->archives[$post['info']['year']]['months'][$post['info']['month']]['posts'][] = $i;
            $this->archives[$post['info']['year']]['months'][$post['info']['month']]['days'][$post['info']['day']]['posts'][] = $i;

            foreach ($post['frontmater'] ['tags'] ?? [] as $tag) {
                $this->tags[trim($tag)][] = $i;
            }
        }
    }
    
    private function writePages()
    {
        if (is_dir($this->getSourcePath('pages'))) {  
            $files = $this->getFiles("pages");
            foreach($files as $file) {
                $content = $this->readFile($file);
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $body = $this->textProcessors->renderHtml($content['body'], pathinfo($file, PATHINFO_EXTENSION));
                $markedup = $this->templateEngine->render('page', ['body' => $body, 'posts' => $this->posts]);
                $this->setOutputPath("$filename.html");
                $this->writeContentToOutputPath(
                    $markedup, 
                    [
                        'page_title' => $content['frontmatter']['title'] 
                            ?? ucfirst(str_replace(['-', '_'], ' ', $filename)),
                        'page_type' => 'page',
                        'frontmatter' => $content['frontmatter']
                    ]
                );
            }
        }
    }

    public function outputSite()
    {
        $files = $this->getFiles("posts");
        $categories = array();

        $this->preProcessFiles($files);
        $this->writePosts();
        $this->writeIndex('index.html', ['template' => 'index']);
        $this->writeIndex('posts.html', ['title' => 'Posts']);
        $this->writePages();
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
            $this->writeIndex("{$newBaseUrl}index.html", ['posts' => $posts['posts'], 'title' => $newTitle]);
            if ($nextStage != null) {
                $this->writeArchive($posts[$nextStage], $order, $nextStage, $newTitle, $newBaseUrl);
            }
        }
    }

    private function writeIndex($target, $options = [])
    {
        $posts = $options['posts'] ?? [];
        if (count($posts)) {
            $rebuiltPosts = array();
            foreach ($posts as $post) {
                $rebuiltPosts[] = $this->posts[$post];
            }
        } else {
            $rebuiltPosts = $this->posts;
        }
        
        $this->setOutputPath($target);
        $title = $options['title'] ?? '';
        $body = $this->templateEngine->render(
            $options['template'] ?? 'listing',
            array(
                'listing_title' => $title,
                'previews' => true,
                'posts' => $rebuiltPosts,
                'site_path' => $this->getRelativeSitePath(),
                'home_path' => $this->getRelativeSitePath()
            )
        );
        $this->writeContentToOutputPath($body, ['page_title' => $title, 'page_type' => 'index']);
    }

    private function writeFeed()
    {
        $feed = $this->templateEngine->render("feed",
            array(
                'posts' => $this->posts,
                'title' => $this->settings['name'] ?? 'Untitled Blog',
                'description' => $this->settings['description'] ?? '',
                'url' => $this->settings['url'] ?? ''
            )
        );
        $this->setOutputPath("feed.xml");
        $this->setLayout('plain');
        $this->writeContentToOutputPath($feed);
    }

    public function getDefaultTheme() {
        return 'blog';
    }
}
