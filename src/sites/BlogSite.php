<?php


namespace nyansapow\sites;


class BlogSite extends AbstractSite
{
    private $posts = [];

    public function getPages() : array
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

    public function getType() : string
    {
        return 'blog';
    }

    public function getDefaultTheme(): string
    {
        return 'blog';
    }
}