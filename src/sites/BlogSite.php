<?php


namespace nyansapow\sites;


class BlogSite extends AbstractSite
{
    private $posts = [];

    public function getPages() : array
    {
        $pages = $this->posts = $this->preProcessFiles($this->getFiles("posts"));
        $pages[] = $this->getIndexPage('index.html', $this->posts, 'index');
//        $this->writeIndex('posts.html', ['title' => 'Posts']);
//        $this->writePages();
//        $this->writeArchive($this->archives, ['months', 'days'], 'years');
//        $this->writeFeed();
        return $pages;
    }

    private function getIndexPage($target, $posts, $title = 'Posts', $template = 'listing')
    {
        return $this->contentFactory->create($template ?? 'listing', $target,
            ['listing_title' => $title, 'previews' => true, 'posts' => $posts]
        );
    }

    private function preProcessFiles($files)
    {
        $pages = [];
        $lastPost = null;

        foreach ($files as $file) {
            if (preg_match("/(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})-(?<title>[A-Za-z0-9\-\_]*)\.(md)/",$file, $matches)) {
                $destinationPath = "{$matches['year']}/{$matches['month']}/{$matches['day']}/{$matches['title']}.html";
                $matches['blog'] = true;
                /** @var BlogContent $page */
                $page = $this->contentFactory->create($this->getSourcePath($file), $destinationPath, $matches);
                $pages[] = $page;
                if($lastPost) {
                    $page->setPrevious($lastPost);
                    $lastPost->setNext($page);
                }
                $this->archives[$matches['year']]['posts'] = array();
                $this->archives[$matches['year']][$matches['month']]['posts'] = array();
                $this->archives[$matches['year']][$matches['month']][$matches['day']]['posts'] = array();
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
