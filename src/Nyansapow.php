<?php

namespace nyansapow;

class Nyansapow
{
    private $options;
    private $source;
    private $destination;
    private $pages = array();
    private $pageFiles = array();
    private $home;
    private $currentDocument;
    
    private function __construct($source, $destination, $options)
    {
        $this->home = dirname(__DIR__);
        if($source == '')
        {
            $source = getcwd();
        }
        
        if(!file_exists($source) && !is_dir($source)) 
        {
            throw new Exception("Input directory `{$source}` does not exist or is not a directory.");
        }
        
        if($destination == '')
        {
            $destination = getcwd() . "/_site";
        }        
        
        $dir = dir($source);
        while (false !== ($entry = $dir->read())) 
        {
            if(!preg_match("/(?<page>.*)(\.)(?<extension>\md|\textile)/i", $entry, $matches))
            {
                continue;
            }            
            $this->pages[] = $matches['page'];
            $this->pageFiles[] = $entry;
        }        
        
        $this->source = $source;
        $this->options = $options;
        $this->destination = $destination;
    }

    public static function open($source, $destination, $options = array())
    {
        $optionsFile = "{$source}site.ini";
        if(file_exists($optionsFile))
        {
            $optionsFileData = parse_ini_file($optionsFile);
            if($options['site-name'] == '' && $optionsFileData['name'] != '')
            {
                $options['site-name'] = $optionsFileData['name'];
            }
        }
        return new Nyansapow($source, $destination, $options);
    }
    
    public function writeAssets()
    {
        var_dump($this->destination);
        self::copyDir("$this->home/themes/default/assets", "{$this->destination}");        
    }
        
    public function getTableOfContents($level = 2, $index = 0)
    {
        $tocTree = array();
        
        $xpath = new DOMXPath($this->currentDocument);;
        $nodes = $xpath->query("//h2|//h3|//h4|//h5|//h6");
        
        for($i = $index; $i < $nodes->length; $i++)
        {
            $nodes->item($i)->setAttribute('id', $nodes->item($i)->nodeValue);
            if($nodes->item($i)->nodeName == "h{$level}")
            {
                if($nodes->item($i + 1)->nodeName == "h{$level}" || $nodes->item($i + 1) === null)
                {
                    $tocTree[] = array(
                        'title' => $nodes->item($i)->nodeValue,
                        'level' => $level - 1,
                        'children' => array()
                    );
                }
                else if($nodes->item($i + 1)->nodeName == "h" . ($level - 1))
                {
                    $tocTree[] = array(
                        'title' => $nodes->item($i)->nodeValue,
                        'level' => $level - 1,
                        'children' => array()
                    );
                    break;
                }
                else
                {
                    $children = $this->getTableOfContents($level + 1, $i + 1);
                    $newIndex = $children['index'];
                    unset($children['index']);
                    $tocTree[] = array(
                        'title' => $nodes->item($i)->nodeValue,
                        'level' => $level - 1,
                        'children' => $children
                    );       
                    $i = $newIndex;
                }
            }
            else 
            {
                break;
            }
        }
        
        if($level > 2) 
        {
            $tocTree['index'] = $i;
        }
        
        return $tocTree;
    }
    
    public function write($files = array())
    {
        /*if(!file_exists($this->destination) && !is_dir($this->destination))
        {
            throw new Exception("Output directory `{$this->destination}` does not exist or is not a directory.");
        }*/
        
        if(count($files) == 0)
        {
            $this->writeAssets();
            // Copy images
            if(is_dir("{$this->source}/images"))
            {
                self::copyDir("{$this->source}/images", "{$this->destination}");
            }
            $files = $this->pageFiles;
        }
        
        $filesWritten = array();
        
        $m = new \Mustache_Engine();   
        $this->currentDocument = new \DOMDocument();
        
        foreach($files as $file)
        {
            if(preg_match("/(?<page>.*)(\.)(?<extension>\md|\textile)/i", $file, $matches))
            {
                switch($matches['page'])
                {
                    case 'Home':
                        $output = "index.html";
                        break;

                    default:
                        $output = "{$matches['page']}.html";
                        break;
                }                
            }
            else if(preg_match("/(?<dir>assets|images)(\/)(.*)(\.*)/", $file, $matches))
            {
                if(!is_dir($matches['dir']))
                {
                    self::mkdir("{$this->destination}/{$matches['dir']}");
                }
                copy("{$this->source}/$file", "{$this->destination}/{$file}");
                continue;
            }
            else
            {
                // Do nothing
                continue;
            }

            $input = file_get_contents("{$this->source}/$file");
            $outputFile = "{$this->destination}/$output";
            
            $preParsed = Parser::preParse($input);
            
            \Michelf\MarkdownExtra::setCallbacks(new Callbacks());
            $markedup = \Michelf\MarkdownExtra::defaultTransform($preParsed);
            $layout = file_get_contents("$this->home/themes/default/templates/layout.mustache");
            
            @$this->currentDocument->loadHTML($markedup);
            $h1s = $this->currentDocument->getElementsByTagName('h1');
            
            Parser::setNyansapow($this);
            Parser::domCreated($this->currentDocument);
            
            $body = $this->currentDocument->getElementsByTagName('body');
            $content = Parser::postParse(
                str_replace(array('<body>', '</body>'), '', $this->currentDocument->saveHTML($body->item(0)))
            );
            
            $webPage = $m->render(
                $layout, 
                array(
                    'body' => $content,
                    'page_title' => $h1s->item(0)->nodeValue,
                    'site_name' => $this->options['site-name'],
                    'date' => date('jS F, Y H:i:s')
                )
            );

            self::writeFile($outputFile, $webPage);
            $filesWritten[] = $output;
        }
    }

    public static function copyDir($source, $destination)
    {
        foreach(glob($source) as $file)
        {
            $newFile = (is_dir($destination) ?  "$destination/" : ''). basename("$file");

            if(is_dir($file))
            {
                self::mkdir($newFile);
                self::copyDir("$file/*", $newFile);
            }
            else
            {
                copy($file, $newFile);
            }
        }
    }
    
    private static function writeFile($path, $contents)
    {
        if(!is_dir(dirname($path))) 
        {
            self::mkdir (dirname($path));
        }
        
        file_put_contents($path, $contents);
    }

    private static function mkdir($path)
    {
        if(!file_exists(dirname($path)))
        {
            self::mkdir(dirname($path));
        }
        else if(!is_writable(dirname($path)))
        {
            throw new Exception("You do not have permissions to create the $path directory.");
        }
        else if(is_dir($path))
        {
            // Skip
        }
        else
        {
            mkdir($path);
        }
        return $path;
    }    
    
    public function getPages()
    {
        return $this->pages;
    }
}

class Exception extends \Exception
{
    
}

