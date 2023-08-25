<?php
namespace foonoo\sites;


use foonoo\asset_pipeline\AssetPipeline;
use foonoo\utils\Cache;
use foonoo\content\AutomaticContentFactory;
use foonoo\content\Content;

/**
 * Base abstract class for all site generators.
 */
abstract class AbstractSite
{
    /**
     * Meta data for the site, read from the site's site.yml file.
     */
    protected array $metaData;

    /**
     * A path to the site relative to the root site.
     */
    private string $path;

    /**
     * Absolute path to the root site.
     */
    private string $sourceRoot;

    /**
     * Absolute path to the destination site.
     */
    private string $destinationRoot;

    /**
     * Data that should be sent to templates for this site when rendered.
     */
    private array $templateData;
    
    /**
     * The path to the site's sources.
     */
    private string $sourcePath = "";
    
    /**
     * The path to the site's destination.
     */
    private string $destinationPath = "";

    /**
     * Used for creating automatic content from files.
     */
    protected AutomaticContentFactory $automaticContentFactory;

    /**
     * An instance of the asset pipeline.
     */
    protected AssetPipeline $assetPipeline;

    private $cache;

    /**
     * Set the path to this site, relative to the root path
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Get the path to this site, relative to the root path
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the root path to the source of this site.
     * @param string $sourceRoot
     */
    public function setSourceRoot(string $sourceRoot): void
    {
        $this->sourceRoot = $sourceRoot;
    }

    /**
     * Set the root path to the destination of this site.
     * @param string $destinationRoot
     */
    public function setDestinationRoot(string $destinationRoot): void
    {
        $this->destinationRoot = $destinationRoot;
    }

    /**
     * Set the metadata for this site.
     * @param $metaData
     */
    public function setMetaData($metaData)
    {
        $this->metaData = $metaData;
    }

    /**
     * Get the metadatd for this site.
     * Data for the metadata usually comes from the site.yml file.
     * @return array
     */
    public function getMetaData()
    {
        return $this->metaData;
    }

    /**
     * Get a value from the metadata.
     *
     * @param $setting
     * @return mixed|null
     */
    public function getSetting($setting)
    {
        return $this->metaData[$setting] ?? null;
    }

    /**
     * Inject the automatic content factory into the site.
     *
     * @param AutomaticContentFactory $automaticContentFactory
     */
    public function setAutomaticContentFactory(AutomaticContentFactory $automaticContentFactory)
    {
        $this->automaticContentFactory = $automaticContentFactory;
    }

    /**
     * Inject data into the template.
     * 
     * @param array $templateData
     * @return void
     */
    public function setTemplateData(array $templateData): void
    {
        $this->templateData = $templateData;
    }

    /**
     * Get template data that is relevant for rendering a particular content page.
     *
     * @param string|null $contentDestination Destination path of the content.
     * @return array
     */
    public function getTemplateData(Content $content = null): array
    {
        $contentDestination = $content !==null ? $content->getDestination() : null;
        $this->templateData['site_menu'] = $this->metaData['menu'] ?? [];
        $this->templateData['site_tagline'] = $this->metaData['tagline'] ?? '';
        $this->templateData['destination'] = $contentDestination;
        
        if ($contentDestination !== null) {
            $contentDestination = $this->getDestinationPath($contentDestination);
            $relativeSitePath = $this->makeRelativeLocation($contentDestination, $this->getDestinationPath());
            return array_merge([
                    'home_path' => $this->makeRelativeLocation($contentDestination, $this->destinationRoot),
                    'site_path' => $relativeSitePath,
                    'site_title' => $this->metaData['title'] ?? '',
                    'date' => date('jS F Y'),
                    'description' => $this->metaData['description'] ?? '',
                    'assets_markup' => $this->assetPipeline->getMarkup($relativeSitePath)
                ],
                $this->templateData
            );
        }
        return array_merge(
            ['assets_markup' => $this->assetPipeline->getMarkup('')], 
            $this->templateData
        );
    }

    /**
     * Generate a string that represents a path that's relative to another.
     */
    private function makeRelativeLocation(string $path, string $relativeTo): string
    {
        // Generate a relative location for the assets
        $dir = substr(preg_replace('#(/\\\\)+|(\\\\/)+|/+|\\\\+#', '/', $path), strlen($relativeTo));
        $relativeLocation = '';
        if ($dir != '' && $dir != '.') {
            $dir .= substr($dir, -1) == '/' ? '' : '/';
            $relativeLocation = str_repeat('../', substr_count($dir, '/') - 1);
        }
        return $relativeLocation == "" ? "./" : $relativeLocation;
    }

    protected function getFiles(string $base = '', bool $includeDirectories=false): array
    {
        $files = array();
        $base = $base == '' ? '' : "$base" . DIRECTORY_SEPARATOR;
        $dir = scandir("{$this->sourceRoot}{$this->path}". DIRECTORY_SEPARATOR . $base, SCANDIR_SORT_ASCENDING);

        foreach ($dir as $file) {
            $path = "{$this->sourceRoot}{$this->path}" . DIRECTORY_SEPARATOR . $base . $file;
            if (array_reduce(
                $this->metaData['excluded_paths'],
                function ($carry, $item) use ($path) {
                    return $carry | fnmatch($item, $path, FNM_NOESCAPE);
                }, false)
            ) { continue; }
            if (is_dir($path) && !$includeDirectories) {
                continue;
            }
            $files[] = "$base$file";
        }

        return $files;
    }
    
    private function getBasePath($root): string
    {
        return preg_replace(["|\\\\+|", "|/+|"], ["\\", "/"], $root . DIRECTORY_SEPARATOR . $this->path . DIRECTORY_SEPARATOR);
    }

    public function getSourcePath(string $path = ""): string
    {
        if(!$this->sourcePath) {
            $this->sourcePath = $this->getBasePath($this->sourceRoot);
        }
        return $this->sourcePath . $path;
    }

    public function getDestinationPath(string $path = ""): string
    {
        if(!$this->destinationPath) {
            $this->destinationPath = $this->getBasePath($this->destinationRoot);
        }
        return $this->destinationPath . $path;
    }

    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function getCache(): Cache
    {
        return $this->cache;
    }

    public function setAssetPipeline(AssetPipeline $assetPipeline) : void
    {
        $this->assetPipeline = $assetPipeline;
    }

    /**
     * Get an instance of the asset pipeline.
     * @return AssetPipeline
     */
    public function getAssetPipeline() : AssetPipeline
    {
        return $this->assetPipeline;
    }

    public abstract function getContent(): array;

    /**
     * Returns a machine readable name of the site type.
     * Whatever value is returned by this function determines the tag that is used in the site.yml file.
     *
     * @return string
     */
    public abstract function getType(): string;

    /**
     * Get the name of the default theme for this site type.
     *
     * @return string
     */
    public abstract function getDefaultTheme(): string;
    
    /**
     * Called to initialize a new site directory with the requirements of a particular site generator.
     */
    public abstract function initialize(string $path, array $metadata) : void;

}
