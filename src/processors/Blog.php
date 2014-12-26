<?php
namespace nyansapow\processors;

class Blog extends \nyansapow\Processor
{
    public function init()
    {
        $this->setTheme('blog');
    }
    
    private function readPost($postFile)
    {
        $file = fopen($postFile, 'r');
        $frontmatterRead = false;
        $postStarted = false;
        $previewRead = false;
        $post = '';
        $preview = '';
        $frontmatter = '';
        while(!feof($file))
        {
            $line = fgets($file);
            if(!$frontmatterRead && !$postStarted && (trim($line) === '<<<<' || trim($line) === '<<<'))
            {
                $frontmatter = $this->readFrontMatter($file);
                $frontmatterRead = true;
                continue;
            }
            $postStarted = true;
            if(preg_match("/\<\!\-\-\s*more\-\-\>/i", $line))
            {
                $previewRead = true;
                continue;
            }
            if(!$previewRead) $preview .= $line;
            $post .= $line;
        }
        
        return array(
            'post' => $post,
            'preview' => $preview,
            'frontmatter' => $frontmatter
        );
    }
    
    private function readFrontMatter($file)
    {
        $frontmatter = '';
        
        do
        {
            $line = fgets($file);
            if(trim($line) === '>>>>' || trim($line) === '>>>') break;
            $frontmatter .= $line;
        }
        while(!feof($file));
        
        return parse_ini_string($frontmatter, true);
    }
    
    protected function getFiles($base = '', $recursive = false)
    {
        $files = parent::getFiles($base, $recursive);
        rsort($files);
        return $files;
    }
    
    public function outputSite()
    {
        $files = $this->getFiles("posts");
        $parsedown = new \Parsedown();
        $posts = array();
        $years = array();
        
        // Preprocess all the files
        foreach($files as $file)
        {
            if(preg_match(
                "/(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})-(?<title>[a-z0-9\-\_]*)\.(md)/", 
                $file, $matches
            )){
                $post = $this->readPost($file);
                $posts[] = array(
                    'body' => $post['post'],
                    'frontmatter' => $post['frontmatter'],
                    'preview' => $post['preview'],
                    'file_info' => $matches,
                    'path' => "{$matches['year']}/{$matches['month']}/{$matches['day']}/{$matches['title']}.html"
                );
                $years[$matches['year']] = array();
            }
        }
        
        foreach($posts as $i => $post)
        {
            $posts[$i]['preview'] = $parsedown->text($post['preview']);
            $markedup = $this->mustache->render(
                file_get_contents("{$this->templates}/post.mustache"),
                array(
                    'date' => date("jS F Y", strtotime("{$post['file_info']['year']}-{$post['file_info']['month']}-{$post['file_info']['day']}")),
                    'title' => $post['frontmatter']['title'],
                    'body' => $parsedown->text($post['body']),
                    'category' => $post['frontmatter']['category'],
                    'next' => isset($posts[$i + 1]) ? $posts[$i + 1] : false,
                    'prev' => isset($posts[$i - 1]) ? $posts[$i - 1] : false,
                )
            );
            $this->outputPage(
                $post['path'], 
                $markedup,
                array(
                    'page_title' => $post['frontmatter']['title']
                )
            );            
            
            $years[$post['file_info']['year']][] = $i;
        }
                
        // Write index page
        foreach($posts as $post)
        {
            $body .= $this->mustache->render(
                file_get_contents("{$this->templates}/post.mustache"),
                array(
                    'date' => date("jS F Y", strtotime("{$post['file_info']['year']}-{$post['file_info']['month']}-{$post['file_info']['day']}")),
                    'title' => $post['frontmatter']['title'],
                    'body' => $post['preview'],
                    'category' => $post['frontmatter']['category']
                )
            );
        }
        $this->outputPage("index.html", $body);
        
        // Write yearly archives
        foreach($years as $year => $postIds)
        {
            $body = '';
            foreach($postIds as $postId)
            {
                $post = $posts[$postId];
                $body .= $this->mustache->render(
                    file_get_contents("{$this->templates}/post.mustache"),
                    array(
                        'date' => date("jS F Y", strtotime("{$post['file_info']['year']}-{$post['file_info']['month']}-{$post['file_info']['day']}")),
                        'title' => $post['frontmatter']['title'],
                        'body' => $post['preview'],
                        'category' => $post['frontmatter']['category']
                    )
                );                
            }
            $this->outputPage("$year/index.html", $body);
        }
        // Write categories
        
        // Write tags
    }
}
