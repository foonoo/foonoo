<?php

namespace nyansapow\generators;

use clearice\io\Io;
use nyansapow\text\TemplateEngine;
use nyansapow\text\TextProcessors;
use ntentan\utils\Filesystem;


/**
 * Processors convert input types into specific types of sites.
 * Some processors may require folders to be organized in specific arrangements. Others may also just expect a bunch
 * or markdown files to be transformed.
 */
abstract class AbstractGenerator
{
    /**
     * The settings stored under the directory of this particular processor
     */
    protected $settings;

    /**
     * Path to the directory of this particular processor
     */
    //private $dir;
    private $layout;
    //protected $baseDir;
    private $theme;
    protected $templates;
    private $outputPath;
    protected $data;
    private $extraAssets;
    protected $textProcessors;
    protected $templateEngine;
    private $frontMatterMarkers = ['---', '<<<', '<<<<', '>>>', '>>>>'];

    /**
     * @var \nyansapow\Nyansapow
     */
    //protected $nyansapow;

    /**
     * Processor constructor.
     *
     * @param TextProcessors $textProcessors
     * @param Io $io
     * @param array $settings
     * @throws \Exception
     */
    public function __construct(TextProcessors $textProcessors, TemplateEngine $templateEngine, $settings = [])
    {
        //var_dump($settings);
        //$this->settings['path'] = $settings['source'] . $settings['base_directory'];
        //$this->settings['base_directory'] = $dir;
        $this->settings = $settings;
        $this->templateEngine = $templateEngine;
        $this->textProcessors = $textProcessors;
        //$this->nyansapow = $nyansapow;

        if (isset($settings['layout'])) {
            $this->setLayout($settings['layout']);
        } else {
            $this->setLayout('layout');
        }

        $this->setTheme($settings['theme'] ?? $this->getDefaultTheme());
    }

//    public function init()
//    {
//
//    }

    private function isExcluded($path)
    {
        foreach ($this->settings['excluded_paths'] as $excludedPath) {
            if (fnmatch($excludedPath, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Loads any extra assets that are in paths pointed to by the assets directive
     * in the site.yml metadata.
     */
    private function loadExtraAssets()
    {
        $this->extraAssets = ['css' => [], 'js' => []];
        $sources = ["np_assets"];

        if (isset($this->settings['assets'])) {
            $sources = array_merge(
                is_array($this->settings['assets']) ?
                    $this->settings['assets'] : [$this->settings['assets']],
                $sources
            );
        }

        foreach ($sources as $source) {
            foreach (['js', 'css'] as $type) {
                $files = glob("{$this->settings['path']}$source/$type/*.$type");
                foreach ($files as $file) {
                    $this->extraAssets[$type][] = "$type/" . basename($file);
                }
            }

            // If a directory named copy exists in the source, just copy it as is
            $copyDir = "{$this->settings['path']}$source/copy";
            if(is_dir($copyDir)) {
                Nyansapow::copyDir("{$this->settings['path']}$source/copy", $this->getDestinationPath("assets"));
            }
        }
    }

    protected function setTheme($theme)
    {
        $this->theme = $theme;
        $builtInTheme = "{$this->settings['home_path']}/themes/{$theme}";
        $customTheme = "{$this->settings['path']}/np_themes/{$theme}";
        
        if (!file_exists($customTheme)) {
            $themePath = $builtInTheme;
        } else {
            $themePath = $customTheme;
        }
        
        if (is_dir($themePath)) {
            if(is_dir("$themePath/assets")) {
                Filesystem::get("$themePath/assets")->copyTo($this->getDestinationPath('assets'));
            }
            $this->templateEngine->prependPath("$themePath/templates");
            $this->loadExtraAssets();            
        } else {
            throw new \Exception("Could not find '$customTheme' directory for '$theme' theme");
        }
    }

    protected function setLayout($layout)
    {
        $this->layout = $layout;
    }

    protected function getFiles($base = '', $recursive = false)
    {
        $files = array();
        $dir = scandir("{$this->settings['path']}/$base", SCANDIR_SORT_ASCENDING);
        foreach ($dir as $file) {
            $path = "{$this->settings['path']}" . ($base == '' ? '' : "$base/") . "$file";
            //if ($this->isExcluded($path)) continue;
            if (array_reduce(
                $this->settings['excluded_paths'],
                function ($carry, $item) use($path) {return $carry | fnmatch($item, $path); },false)
            ) continue;
            if (is_dir($path) && $recursive) {
                $files = array_merge($files, $this->getFiles($path, true));
            } else if (!is_dir($path)) {
                //@todo replace $this->>settings ... with $this->settings['path']
                $path = substr($path, strlen(realpath($this->settings['source'] . $this->settings['base_directory'])));
                $files[] = $path;
            }
        }
        return $files;
    }

    /**
     * Returns the relative path to the site directory.
     * 
     * @return type
     */
    protected function getRelativeSitePath()
    {
        return $this->getRelativeBaseLocation($this->outputPath);
    }

    /**
     * Returns the relative path to the base directory of all sites when using multiple sites.
     * 
     * @return string
     */
    protected function getRelativeHomePath()
    {
        return $this->getRelativeBaseLocation($this->settings['base_directory'] . $this->outputPath);
    }

    /**
     * @param $content
     * @param array $overrides
     * @throws \ntentan\honam\exceptions\FileNotFoundException
     */
    protected function writeContentToOutputPath($content, $overrides = array())
    {
        $params = array_merge([
                'body' => $content,
                'home_path' => $this->getRelativeHomePath(),
                'site_path' => $this->getRelativeSitePath(),
                'site_name' => $this->settings['name'] ?? '',
                'date' => date('jS F Y')
            ],
            $overrides
        );
        $webPage = $this->templateEngine->render($this->layout, $params);
        $outputPath = $this->getDestinationPath($this->outputPath);
        if (!is_dir(dirname($outputPath))) {
            Filesystem::get(dirname($outputPath));
        }
        file_put_contents($outputPath, $webPage);
    }    

    protected function getRelativeBaseLocation($dir)
    {
        // Generate a relative location for the assets
        $assetsLocation = '';
        if ($dir != '' && $dir != '.') {
            $dir .= substr($dir, -1) == '/' ? '' : '/';
            $assetsLocation = str_repeat('../', substr_count($dir, '/') - 1);
        }
        return $assetsLocation;
    }

    public function setOutputPath($path)
    {
        if ($path[0] == '/') {
            $path = substr($path, 1);
        }
        $this->outputPath = $path;
        $this->textProcessors->setPathToBase($this->getRelativeBaseLocation($path));
    }

    protected function getSourcePath($path)
    {
        return realpath($this->settings['source'] . $this->settings['base_directory']) . "/" . $path;
    }

    protected function getDestinationPath($path)
    {
        return $this->settings["destination"] . $this->settings['base_directory'] . $path;
    }

    protected function readFile($textFile)
    {
        $file = fopen($this->getSourcePath($textFile), 'r');
        $frontmatterRead = false;
        $postStarted = false;
        $body = '';
        $frontmatter = array();

        try {
            while (!feof($file)) {
                $line = fgets($file);
                if (!$frontmatterRead && !$postStarted && array_search(trim($line), $this->frontMatterMarkers) !== false) {
                    $frontmatter = $this->readFrontMatter($file);
                    $frontmatterRead = true;
                    continue;
                }
                $postStarted = true;
                $body .= $line;
            }
        } catch (ParseException $e) {
            throw new \Exception("Error parsing front matter for $textFile. {$e->getMessage()}");
        }

        if(!is_array($frontmatter)) {
            throw new \Exception("Error parsing front matter for $textFile.");
        }


        return ['body' => $body, 'frontmatter' => $frontmatter];
    }

    private function readFrontMatter($file)
    {
        $frontmatter = '';
        do {
            $line = fgets($file);
            if (array_search(trim($line), $this->frontMatterMarkers) !== false) break;
            $frontmatter .= $line;
        } while (!feof($file));

        return $this->textProcessors->parseYaml($frontmatter);
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public abstract function outputSite();
    
    protected abstract function getDefaultTheme();
}
