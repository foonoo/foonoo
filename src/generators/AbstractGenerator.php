<?php

namespace nyansapow\generators;

use Exception;
use ntentan\utils\exceptions\FileAlreadyExistsException;
use ntentan\utils\exceptions\FileNotWriteableException;
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
    private $layout;
    private $theme;
    protected $templates;
    private $outputPath;
    protected $data;
    private $extraAssets;
    protected $textProcessors;
    protected $templateEngine;
    private $frontMatterMarkers = ['---', '<<<', '<<<<', '>>>', '>>>>'];


    /**
     * Processor constructor.
     *
     * @param TextProcessors $textProcessors
     * @param TemplateEngine $templateEngine
     * @param array $settings
     * @throws Exception
     */
    public function __construct(TextProcessors $textProcessors, TemplateEngine $templateEngine, $settings = [])
    {
        $this->settings = $settings;
        $this->templateEngine = $templateEngine;
        $this->textProcessors = $textProcessors;
        $this->layout = $settings['layout'] ?? 'layout';

        $this->setTheme($settings['theme'] ?? $this->getDefaultTheme());
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
        }
    }

    protected function setTheme($theme)
    {
        $this->theme = $theme;
        $builtInTheme = __DIR__ . "/../../themes/{$theme}";
        $customTheme = "{$this->settings['path']}/np_themes/{$theme}";
        
        if (!file_exists($customTheme)) {
            $themePath = $builtInTheme;
        } else {
            $themePath = $customTheme;
        }

        if (is_dir($themePath)) {
            if(is_dir("$themePath/assets")) {
                Filesystem::directory("$themePath/assets")
                    ->getFiles()
                    ->copyTo($this->getDestinationPath('assets'));
            }
            $this->templateEngine->prependPath("$themePath/templates");
            $this->loadExtraAssets();            
        } else {
            throw new Exception("Could not find '$customTheme' directory for '$theme' theme");
        }
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
     * @throws FileAlreadyExistsException
     * @throws FileNotWriteableException
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
            Filesystem::directory(dirname($outputPath))->create(true);
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
        //$this->textProcessors->setPathToBase($this->getRelativeBaseLocation($path));
    }

    protected function getSourcePath($path)
    {
        return realpath($this->settings['source'] . $this->settings['base_directory']) . "/" . $path;
    }

    protected function getDestinationPath($path)
    {
        return $this->settings["destination"] . $this->settings['base_directory'] . $path;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    public abstract function outputSite();
    
    protected abstract function getDefaultTheme();
}
