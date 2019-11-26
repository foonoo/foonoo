<?php


namespace nyansapow\sites;


class BlogSite extends AbstractSite
{
    private $posts = [];
    private $archives = [];

    public function getPages() : array
    {
        $pages = $this->posts = $this->preProcessFiles($this->getFiles("posts"));
        $pages[] = $this->getIndexPage('index.html', $this->posts, '');
        $pages[] = $this->getIndexPage('posts.html', $this->posts);
        $pages = array_merge($pages, $this->getArchive($this->archives, ['months', 'days'], 'years'));
//        $this->writeFeed();
        return $pages;
    }

    private function getIndexPage($target, $posts, $title = 'Posts', $template = 'listing')
    {
        return $this->contentFactory->create($template ?? 'listing', $target,
            [
                'listing_title' => $title,
                'previews' => true,
                'posts' => array_map(function($x){ return $x->getMetaData(); }, $posts)
            ]
        );
    }

    private function getArchive($archive, $order = array(), $stage = null, $title = 'Archive', $baseUrl = '')
    {
        $pages = [];
        $nextStage = array_shift($order);
        foreach ($archive as $value => $posts) {
            $newTitle = $this->formatValue($stage, $value) . " $title";
            $newBaseUrl = "$baseUrl$value/";
            $pages[]= $this->getIndexPage("{$newBaseUrl}index.html", $posts['posts'], $newTitle);
            if ($nextStage != null) {
                $pages = array_merge($pages, $this->getArchive($posts[$nextStage], $order, $nextStage, $newTitle, $newBaseUrl));
            }
        }

        return $pages;
    }

    private function formatValue($stage, $value)
    {
        switch ($stage) {
            case 'years':
                return $value;
            case 'months':
                return $value;
            case 'days':
                return $value;
        }
    }

    private function preProcessFiles($files)
    {
        $pages = [];
        $lastPost = null;

        foreach ($files as $file) {
            if (preg_match("/(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})-(?<title>[A-Za-z0-9\-\_]*)\.(md)/",$file, $matches)) {
                $destinationPath = "{$matches['year']}/{$matches['month']}/{$matches['day']}/{$matches['title']}.html";
                // Force content factory to generate blog content
                $matches['blog'] = true;
                /** @var BlogContent $page */
                $page = $this->contentFactory->create($this->getSourcePath($file), $destinationPath, $matches);
                $pages[] = $page;
                if($lastPost) {
                    $page->setPrevious($lastPost);
                    $lastPost->setNext($page);
                }

                if(!isset($this->archives[$matches['year']])) {
                    $this->archives[$matches['year']] = ['posts' => []];
                }
                if(!isset($this->archives[$matches['year']][$matches['month']])) {
                    $this->archives[$matches['year']][$matches['month']] = ['posts' => []];
                }
                if(!isset($this->archives[$matches['year']][$matches['month']][$matches['day']])) {
                    $this->archives[$matches['year']][$matches['month']][$matches['day']] = ['posts' => []];
                }
                $this->archives[$matches['year']]['posts'][] = $page;
                $this->archives[$matches['year']]['months'][$matches['month']]['posts'][] = $page;
                $this->archives[$matches['year']]['months'][$matches['month']]['days'][$matches['day']]['posts'][] = $page;
            }
        }

        return $pages;
    }

    public function getType() : string
    {
        return 'blog';
    }

    public function getDefaultTheme(): string
    {
        return 'blog';
    }
}
