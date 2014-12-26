<?php
namespace nyansapow\processors;

class Blog extends \nyansapow\Processor
{
    private $posts = array();
    
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
        $archives = array();
        
        // Preprocess all the files
        foreach($files as $file)
        {
            if(preg_match(
                "/(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})-(?<title>[a-z0-9\-\_]*)\.(md)/", 
                $file, $matches
            )){
                $post = $this->readPost($file);
                $this->posts[] = array(
                    'body' => $parsedown->text($post['post']),
                    'title' => $post['frontmatter']['title'],
                    'date' => date("jS F Y", strtotime("{$matches['year']}-{$matches['month']}-{$matches['day']}")),
                    'preview' => $parsedown->text($post['preview']),
                    'path' => "{$matches['year']}/{$matches['month']}/{$matches['day']}/{$matches['title']}.html",
                    'category' => $post['frontmatter']['category'],
                    'frontmatter' => $post['frontmatter'],
                    'info' => $matches
                );
                $archives[$matches['year']]['posts'] = array();
                $archives[$matches['year']][$matches['month']]['posts'] = array();
                $archives[$matches['year']][$matches['month']][$matches['day']]['posts'] = array();
            }
        }
        
        foreach($this->posts as $i => $post)
        {
            $markedup = $this->mustache->render(
                'post',
                array_merge(
                    $post,
                    array(
                        'next' => isset($this->posts[$i - 1]) ? $this->posts[$i - 1] : false,
                        'prev' => isset($this->posts[$i + 1]) ? $this->posts[$i + 1] : false,
                    )
                )
            );
            $this->outputPage(
                $post['path'], 
                $markedup,
                array(
                    'page_title' => $post['frontmatter']['title']
                )
            );            
            
            $archives[$post['info']['year']]['posts'][] = $i;
            $archives[$post['info']['year']]['months'][$post['info']['month']]['posts'][] = $i;
            $archives[$post['info']['year']]['months'][$post['info']['month']]['days'][$post['info']['day']]['posts'][] = $i;
        }
                
        // Write index page
        $this->writeIndex('index.html');
        $this->writeArchive($archives, array('months', 'days'), array('Y', 'F,', 'jS'));
        
        // Write categories
        $this-
        
        // Write tags
    }
    
    private function writeArchive($archive, $order, $title = 'Archive', $baseUrl = '')
    {
        $nextStage = array_shift($order);
        foreach($archive as $value => $posts)
        {
            $newTitle = "$value $title";
            $newBaseUrl = "$baseUrl$value/";
            $this->writeIndex("{$newBaseUrl}index.html", $posts['posts'], $newTitle);
            if($nextStage != null)
            {
                $this->writeArchive($posts[$nextStage], $order, $newTitle, $newBaseUrl);
            }
        }
    }
   
    private function writeIndex($target, $posts = array(), $title = null)
    {
        if(count($posts))
        {
            $rebuiltPosts = array();
            foreach($posts as $post)
            {
                $rebuiltPosts[] = $this->posts[$post];
            }
        }
        else
        {
            $rebuiltPosts = $this->posts;
        }
        
        $body = $this->mustache->render(
            'listing',
            array(
                'listing_title' => $title,
                'previews' => true,
                'posts' => $rebuiltPosts,
            )
        );
        $this->outputPage($target, $body);        
    }
}
