<?php

namespace nyansapow\processors;

use ntentan\honam\TemplateEngine;
use nyansapow\Nyansapow;
use nyansapow\Parser;
use clearice\io\Io;
use \Symfony\Component\Yaml\Parser as YamlParser;
use \Symfony\Component\Yaml\Exception\ParseException;


/**
 * Processors convert input types into specific types of sites.
 * Some processors may require folders to be organized in specific arrangements. Others may also just expect a bunch
 * or markdown files to be transformed.
 */
abstract class AbstractProcessor
{
    protected $settings;
    private $dir;
    private $layout;
    protected $baseDir;
    private $theme;
    protected $templates;
    private $outputPath;
    protected $data;
    private $extraAssets;
    private $yamlParser;
    private $frontMatterMarkers = ['---', '<<<', '<<<<', '>>>', '>>>>'];

    /**
     * @var \nyansapow\Nyansapow
     */
    protected $nyansapow;
    protected $io;

    /**
     * Processor constructor.
     *
     * @param Io $io
     * @param array $settings
     * @param string $dir
     */
    public function __construct(Nyansapow $nyansapow, Io $io, YamlParser $yamlParser, $settings = [], $dir = '')
    {
        $this->dir = $dir;
        $this->settings = $settings;

        if (isset($settings['layout'])) {
            $this->setLayout($settings['layout']);
        } else {
            $this->setLayout('layout');
        }

        if (isset($settings['theme'])) {
            $this->setTheme($settings['theme']);
        }

        $this->io = $io;
        $this->nyansapow = $nyansapow;
        $this->yamlParser = $yamlParser;

        $this->init();
    }

    public function init()
    {

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
                $files = glob("{$this->dir}$source/$type/*.$type");
                foreach ($files as $file) {
                    $this->extraAssets[$type][] = "$type/" . basename($file);
                }
            }

            // If a directory named copy exists in the source, just copy it as is
            $copyDir = "{$this->dir}$source/copy";
            if(is_dir($copyDir)) {
                Nyansapow::copyDir("{$this->dir}$source/copy", $this->nyansapow->getDestination() . "/assets");                
            }
        }
    }

    public function setTheme($theme)
    {
        $this->theme = $theme;
        if (!file_exists("{$this->dir}/np_themes/{$theme}")) {
            $theme = $this->nyansapow->getHome() . "/themes/{$theme}";
        } else {
            $theme = "{$this->dir}/np_themes/{$theme}";
        }
        
        Nyansapow::copyDir("$theme/assets/*", $this->nyansapow->getDestination() . "/assets");
        TemplateEngine::prependPath("$theme/templates");
        $this->loadExtraAssets();
    }

    public function getDir()
    {
        return $this->dir;
    }

    public function setBaseDir($baseDir)
    {
        $this->baseDir = $baseDir;
    }

    protected function setLayout($layout)
    {
        $this->layout = $layout;
    }

    protected function getFiles($base = '', $recursive = false)
    {
        $files = array();
        $dir = scandir("{$this->dir}/$base", SCANDIR_SORT_ASCENDING);
        foreach ($dir as $file) {
            $path = "{$this->dir}" . ($base == '' ? '' : "$base/") . "$file";
            if ($this->nyansapow->isExcluded($path)) continue;
            if (is_dir($path) && $recursive) {
                $files = array_merge($files, $this->getFiles($path, true));
            } else if (!is_dir($path)) {
                $path = substr($path, strlen(realpath($this->nyansapow->getSource() . $this->baseDir)));
                $files[] = $path;
            }
        }
        return $files;
    }

    protected function getSitePath()
    {
        return $this->getRelativeBaseLocation($this->outputPath);
    }

    protected function getHomePath()
    {
        return $this->getRelativeBaseLocation($this->baseDir . $this->outputPath);
    }

    /**
     * @param $content
     * @param array $overrides
     * @throws \ntentan\honam\exceptions\FileNotFoundException
     */
    protected function outputPage($content, $overrides = array())
    {
        $params = array_merge([
                'body' => $content,
                'home_path' => $this->getHomePath(),
                'site_path' => $this->getSitePath(),
                'site_name' => $this->settings['name'] ?? '',
                'date' => date('jS F Y')
            ],
            $overrides
        );
        $webPage = TemplateEngine::render($this->layout, $params);
        self::writeFile($this->getDestinationPath($this->outputPath), $webPage);
    }

    protected static function writeFile($path, $contents)
    {
        if (!is_dir(dirname($path))) {
            Nyansapow::mkdir(dirname($path));
        }
        file_put_contents($path, $contents);
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
        Parser::setPathToBase($this->getRelativeBaseLocation($path));
    }

    protected function getSourcePath($path)
    {
        return realpath($this->nyansapow->getSource() . $this->baseDir) . "/" . $path;
    }

    protected function getDestinationPath($path)
    {
        return $this->nyansapow->getDestination() . $this->baseDir . $path;
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

        return $this->yamlParser->parse($frontmatter);        
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public abstract function outputSite();
}
