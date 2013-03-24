<?php
class Nyansapow
{
    private $options;
    private $source;
    private $filesWritten = array();
    
    private function __construct($source, $options)
    {
        if($source == '')
        {
            throw new NyansapowException("Please specify where your wiki source files are located.");
        }
        
        if(!file_exists($source) && !is_dir($source)) 
        {
            throw new NyansapowException("Input directory `{$source}` does not exist or is not a directory.");
        }
        
        $this->source = $source;
        $this->options = $options;
    }

    public static function open($source, $options = array())
    {
        return new Nyansapow($source, $options);
    }
    
    public function write($destination)
    {
        if($destination == '')
        {
            $destination = getcwd();
        }

        if(!file_exists($destination) && !is_dir($destination))
        {
            throw new NyansapowException("Output directory `{$destination}` does not exist or is not a directory.");
        }
        
        
        $dir = dir($this->source);

        // Copy assets from the theme
        self::copyDir('themes/default/assets', "{$destination}");

        $m = new Mustache_Engine();
        $layout = file_get_contents('themes/default/templates/layout.mustache');

        while (false !== ($entry = $dir->read())) 
        {
            if(preg_match("/(?<page>.*)(\.)(?<extension>\md|\textile)/i", $entry, $matches))
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

                $outputFile = "{$destination}/~$output";
                $inputFile = "{$this->source}/$entry";
                $content = \Michelf\MarkdownExtra::defaultTransform(file_get_contents($inputFile));

                $webPage = $m->render(
                    $layout, 
                    array(
                        'body' => $content,
                        'title' => $this->options['name']
                    )
                );

                file_put_contents($outputFile, $webPage);
                $this->filesWritten[] = $output;
            }
        }

        foreach($this->filesWritten as $fileWritten)
        {
            $inputFile = fopen("{$destination}/~$fileWritten", 'r');
            $outputFile = fopen("{$destination}/$fileWritten", 'w');
            while(!feof($inputFile))
            {
                fputs($outputFile, Nyansapow::parse(fgets($inputFile)));
            }
            fclose($inputFile);
            fclose($outputFile);
            unlink("{$destination}/~$fileWritten");
        }        
    }
    
    private function parse($line)
    {
        return preg_replace_callback(
            "|\[\[(?<markup>.*)\]\]|",
            function($matches)
            {
                $link = str_replace(array(' ', '/'), '-', $matches['markup']) . ".html";
                foreach($this->filesWritten as $fileWritten)
                {
                    if(strtolower($fileWritten) == strtolower($link))
                    {
                        $link = $fileWritten;
                        break;
                    }
                }
                return "<a href='{$link}'>{$matches['markup']}</a>";
            },
            $line
        );
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
}


class NyansapowException extends Exception
{
    
}

