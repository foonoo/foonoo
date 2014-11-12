<?php
namespace nyansapow\processors;

class Blog extends \nyansapow\Processor
{
    public function init()
    {
        $this->setTheme('blog');
    }
    
    private function readPost($post, &$frontmatter)
    {
        $file = fopen($post, 'r');
        $frontmatterRead = false;
        $postStarted = false;
        $post = '';
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
            $post .= $line;
        }
        
        return $post;
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
    
    public function outputSite()
    {
        $files = $this->getFiles("posts");
        $structure = array();
        $parsedown = new \Parsedown();
        
        foreach($files as $file)
        {
            if(preg_match(
                "/(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})-(?<title>[a-z0-9\-\_]*)\.(md)/", 
                $file, $matches
            )){
                $structure[$matches['year']] = array(
                    'file' => $file,
                    'title' => $matches['title']
                );
                $post = $this->readPost($file, $frontmatter);
                $markedup = $this->mustache->render(
                    file_get_contents("{$this->templates}/post.mustache"),
                    array(
                        'date' => date("jS F Y"),
                        'title' => $frontmatter['title'],
                        'body' => $parsedown->text($post),
                        'category' => $frontmatter['category']
                    )
                );
                $this->outputPage(
                    "{$matches['year']}/{$matches['month']}/{$matches['day']}/{$matches['title']}.html", 
                    $markedup,
                    array(
                        'page_title' => $frontmatter['title']
                    )
                );
            }
        }
                
        // Write index page
        
        // Write yearly archives
        
        // Write categories
        
        // Write tags
    }
}
