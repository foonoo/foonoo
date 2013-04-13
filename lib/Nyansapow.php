<?php

require "php-markdown/Michelf/Markdown.php";
require "php-markdown/Michelf/MarkdownExtra.php";
require "mustache/src/Mustache/Autoloader.php";
require "NyansapowParser.php";

Mustache_Autoloader::register();

class Nyansapow
{
    private $options;
    private $source;
    private $destination;
    private $pages = array();
    private $pageFiles = array();
    private $home;
    
    private function __construct($source, $destination, $options)
    {
        $this->home = dirname(__DIR__);
        if($source == '')
        {
            throw new NyansapowException("Please specify where your wiki source files are located.");
        }
        
        if(!file_exists($source) && !is_dir($source)) 
        {
            throw new NyansapowException("Input directory `{$source}` does not exist or is not a directory.");
        }
        
        if($destination == '')
        {
            $destination = getcwd();
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
        return new Nyansapow($source, $destination, $options);
    }
    
    public function writeAssets()
    {
        //echo "Writing assets ...\n";
        self::copyDir("$this->home/themes/default/assets", "{$this->destination}");        
    }
    
    public function write($files = array())
    {
        if(!file_exists($this->destination) && !is_dir($this->destination))
        {
            throw new NyansapowException("Output directory `{$destination}` does not exist or is not a directory.");
        }
        
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
            elseif(preg_match("/(?<dir>assets|images)(\/)(.*)(\.*)/", $file, $matches))
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

            $outputFile = "{$this->destination}/~$output";
            $inputFile = "{$this->source}/$file";
            
            $m = new Mustache_Engine();   
            $layout = file_get_contents("$this->home/themes/default/templates/layout.mustache");
            $content = \Michelf\MarkdownExtra::defaultTransform(file_get_contents($inputFile));
            
            $document = new DOMDocument();
            @$document->loadHTML($content);
            $h1s = $document->getElementsByTagName('h1');
            
            $webPage = $m->render(
                $layout, 
                array(
                    'body' => $content,
                    'title' => $h1s->item(0)->nodeValue,
                    'name' => $this->options['name'],
                    'date' => date('jS F, Y H:i:s')
                )
            );

            file_put_contents($outputFile, $webPage);
            $filesWritten[] = $output;
        }        
        
        NyansapowParser::setNyansapow($this);

        foreach($filesWritten as $fileWritten)
        {
            $inputFilePath = "{$this->destination}/~$fileWritten";
            $inputFile = fopen($inputFilePath, 'r');
            
            if($inputFile === false)
            {
                die("could not open input file $inputFilePath\n");
            }
            
            $outputFilePath = "{$this->destination}/$fileWritten";
            $outputFile = fopen($outputFilePath, 'w');
            
            if($outputFile == false)
            {
                die("could not open input file $outputFilePath\n");;
            }
            
            while(!feof($inputFile))
            {
                fputs($outputFile, NyansapowParser::parse(fgets($inputFile)));
            }
            fclose($inputFile);
            fclose($outputFile);
            unlink("{$this->destination}/~$fileWritten");
        }        
    }

    public static function copyDir($source, $destination)
    {
        foreach(glob($source) as $file)
        {
            print "Copying $file ...\n";
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

    private static function mkdir($path)
    {
        if(!\is_writable(dirname($path)))
        {
            throw new NyansapowException("You do not have permissions to create the $path directory.");
        }
        else if(\is_dir($path))
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

class NyansapowException extends Exception
{
    
}

