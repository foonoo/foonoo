<?php


namespace nyansapow\sites;


class BlogSite extends AbstractSite
{
    private $posts = [];
    private $archives = [];
    private $blogContentFactory;

    public function __construct(BlogContentFactory $blogContentFactory)
    {
        $this->blogContentFactory = $blogContentFactory;
    }

    public function getPages() : array
    {
        $pages = $this->posts = $this->preProcessFiles($this->getFiles("posts"));
        $pages[] = $this->getIndexPage('index.html', $this->posts, '');
        $pages[] = $this->getIndexPage('posts.html', $this->posts);
        $pages = array_merge($pages, $this->getArchive($this->archives, ['months', 'days'], 'years'));
        return $pages;
    }

    private function getIndexPage($target, $posts, $title = 'Posts', $template = 'listing')
    {
        return $this->blogContentFactory->createListing($posts, $target, ['listing_title' => $title, 'previews' => true]);
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
                return date("M", $value);
            case 'days':
                return $value;
        }
    }

    private function preProcessFiles($files)
    {
        $pages = [];
        /** @var BlogPostContent $lastPost */
        $lastPost = null;

        foreach ($files as $file) {
            if (preg_match("/(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})-(?<title>[A-Za-z0-9\-\_]*)\.(md)/",$file, $matches)) {
                $destinationPath = "{$matches['year']}/{$matches['month']}/{$matches['day']}/{$matches['title']}.html";
                // Force content factory to generate blog content
                $page = $this->blogContentFactory->createPost($this->getSourcePath($file), $destinationPath, $matches);
                $pages[] = $page;
                if($lastPost) {
                    $page->setPrevious($lastPost);
                    $lastPost->setNext($page);
                }
                $this->addPostToArchive($page, $matches);
            }
        }

        return $pages;
    }

    private function addPostToArchive($page, $matches)
    {
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

    public function getType() : string
    {
        return 'blog';
    }

    public function getDefaultTheme(): string
    {
        return 'blog';
    }
}
