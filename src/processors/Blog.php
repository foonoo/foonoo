<?php
namespace nyansapow\processors;

use ntentan\honam\TemplateEngine;

class Blog extends \nyansapow\Processor
{
    private $posts = array();
    
    public function init()
    {
        $this->setTheme('blog');
    }
    
    private function splitPost($post)
    {
        $previewRead = false;
        $lines = explode("\n", $post);
        $preview = '';
        $body = '';
        $moreLink = false;
        
        foreach($lines as $line)
        {
            if(preg_match("/(?<preview>.*)(?<tag>\<\!\-\-\s*more\s*\-\-\>)(?<line>.*)/i", $line, $matches))
            {
                $preview .= "{$matches['preview']}\n";
                $body .= "{$matches['preview']} {$matches['line']}\n";
                $previewRead = true;
                $moreLink = true;
                continue;
            }
            if(!$previewRead) {
                $preview .= "$line\n";
            }
            $body .= "$line\n";
        }
        
        return array(
            'post' => $body,
            'preview' => $preview,
            'more_link' =>$moreLink
        );
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
        $tags = array();
        $categories = array();
        $archives = array();
        
        // Preprocess all the files
        foreach($files as $file)
        {
            if(preg_match(
                "/(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})-(?<title>[a-z0-9\-\_]*)\.(md)/", 
                $file, $matches
                )
            ){
                $post = $this->readFile($file);
                $splitPost = $this->splitPost($post['body']);
                $this->posts[] = array(
                    'body' => $splitPost['post'],
                    'title' => $post['frontmatter']['title'],
                    'date' => date("jS F Y", strtotime("{$matches['year']}-{$matches['month']}-{$matches['day']}")),
                    'preview' => $splitPost['preview'],
                    'path' => "{$matches['year']}/{$matches['month']}/{$matches['day']}/{$matches['title']}.html",
                    'category' => $post['frontmatter']['category'],
                    'frontmatter' => $post['frontmatter'],
                    'info' => $matches,
                    'more_link' => $splitPost['more_link'],
                    'file' => $file,
                    'author' => $post['frontmatter']['author'] ?? $this->settings['author']
                );
                $archives[$matches['year']]['posts'] = array();
                $archives[$matches['year']][$matches['month']]['posts'] = array();
                $archives[$matches['year']][$matches['month']][$matches['day']]['posts'] = array();
            }
        }
        
        foreach($this->posts as $i => $post)
        {
            $this->setOutputPath($post['path']);
            $this->posts[$i]['body'] = \nyansapow\TextRenderer::render($post['body'], $post['file']);
            $this->posts[$i]['preview'] = \nyansapow\TextRenderer::render($post['preview'], $post['file']);
            
            $markedup = TemplateEngine::render(
                'post',
                array_merge(
                    $this->posts[$i],
                    array(
                        'next' => isset($this->posts[$i - 1]) ? $this->posts[$i - 1] : false,
                        'prev' => isset($this->posts[$i + 1]) ? $this->posts[$i + 1] : false,
                    )
                )
            );
            
            $this->outputPage(
                $markedup,
                array(
                    'page_title' => $post['frontmatter']['title']
                )
            );            
            
            $archives[$post['info']['year']]['posts'][] = $i;
            $archives[$post['info']['year']]['months'][$post['info']['month']]['posts'][] = $i;
            $archives[$post['info']['year']]['months'][$post['info']['month']]['days'][$post['info']['day']]['posts'][] = $i;
            
            $articleTags = explode(",", $post['frontmatter']['tags']);
            //$categories[trim($post['frontmatter']['category'])][] = $i;
            
            foreach($articleTags as $tag)
            {
                $tags[trim($tag)][] = $i;
            }
        }
                
        // Write index page
        $this->writeIndex('index.html');
        $this->writeArchive($archives, array('months', 'days'), 'years');
        
        // Write RSS feed
        $this->writeFeed();
        
        // Write categories
        
        // Write tags
        
    }
    
    private function formatValue($stage, $value)
    {
        switch ($stage)
        {
            case 'years': return $value;
            case 'months': return $this->getMonthName($value);
            case 'days': return $this->rank($value);
        }
    }
    
    private function rank($number)
    {
        switch(substr($number, -1))
        {
            case 1: return  "{$number}st";
            case 2: return  "{$number}nd";
            case 3: return  "{$number}rd";
            default: return "{$number}th";
        }
    }
    
    private function getMonthName($number)
    {
        switch($number)
        {
            case  1: return 'January';
            case  2: return 'February';
            case  3: return 'March';
            case  4: return 'April';
            case  5: return 'May';
            case  6: return 'June';
            case  7: return 'July';
            case  8: return 'August';
            case  9: return 'September';
            case 10: return 'October';
            case 11: return 'November';
            case 12: return 'December';
        }
    }
    
    private function writeArchive($archive, $order = array(), $stage = null, $title = 'Archive', $baseUrl = '')
    {
        $nextStage = array_shift($order);
        
        foreach($archive as $value => $posts)
        {
            $newTitle = $this->formatValue($stage, $value) . " $title";
            $newBaseUrl = "$baseUrl$value/";
            $this->writeIndex("{$newBaseUrl}index.html", $posts['posts'], $newTitle);
            if($nextStage != null)
            {
                $this->writeArchive($posts[$nextStage], $order, $nextStage, $newTitle, $newBaseUrl);
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
        $feed = TemplateEngine::render("feed.tpl.php",
            array(
                'posts' => $this->posts,
                'title' => $this->settings['name'],
                'description' => $this->settings['description'],
                'url' => $this->settings['url']
            )
        );
        $this->setOutputPath("feed.xml");
        $this->setLayout('plain');
        $this->outputPage($feed);
    }
}
