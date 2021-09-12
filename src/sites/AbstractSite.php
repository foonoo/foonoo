<?php


namespace foonoo\sites;


use foonoo\asset_pipeline\AssetPipeline;
use foonoo\utils\Cache;
use foonoo\content\AutomaticContentFactory;

/**
 * Base abstract class for all sites
 * @package nyansapow\sites
 */
abstract class AbstractSite
{
    /**
     * Meta data for the site, read from the site's site.yml file.
     * @var array
     */
    protected $metaData;

    /**
     * A path to the site relative to the root site.
     * @var string
     */
    private $path;

    /**
     * Absolute path to the root site.
     * @var string
     */
    private $sourceRoot;

    /**
     * Absolute path to the destination site.
     * @var string
     */
    private $destinationRoot;

    /**
     * Data that should be sent to templates for this site when rendered.
     * @var array
     */
    private $templateData;

    private $sourcePath;
    
    private $destinationPath;

    /**
     * Used for creating automatic content from files.
     * @var AutomaticContentFactory
     */
    protected $automaticContentFactory;

    /**
     * @var AssetPipeline
     */
    protected $assetPipeline;

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

    public function setTemplateData(array $templateData): void
    {
        $this->templateData = $templateData;
    }

    /**
     * Get template data that is relevant for rendering a particular content item.
     *
     * @param string|null $contentDestination Destination path of the content.
     * @return array
     */
    public function getTemplateData(string $contentDestination = null): array
    {
        if ($contentDestination !== null) {
            $relativeSitePath = $this->makeRelativeLocation($contentDestination, $this->getDestinationPath());
            return array_merge([
                    'home_path' => $this->makeRelativeLocation($contentDestination, $this->destinationRoot),
                    'site_path' => $relativeSitePath,
                    'site_name' => $this->metaData['name'] ?? '',
                    'date' => date('jS F Y'),
                    'description' => $this->metaData['description'] ?? '',
                    'assets_markup' => $this->assetPipeline->getMarkup($relativeSitePath)
                ],
                $this->templateData
            );
        }
        return array_merge(['assets_markup' => $this->assetPipeline->getMarkup('')], $this->templateData);
    }

    private function makeRelativeLocation($path, $relativeTo): string
    {
        // Generate a relative location for the assets
        $dir = substr(preg_replace('#/+#', '/', $path), strlen($relativeTo));
        $relativeLocation = '';
        if ($dir != '' && $dir != '.') {
            $dir .= substr($dir, -1) == '/' ? '' : '/';
            $relativeLocation = str_repeat('../', substr_count($dir, '/') - 1);
        }
        return $relativeLocation == "" ? "./" : $relativeLocation;
    }

    protected function getFiles(string $base = '', bool $recursive = false): array
    {
        $files = array();
        $base = $base == '' ? '' : "$base" . DIRECTORY_SEPARATOR;
        $dir = scandir("{$this->sourceRoot}{$this->path}". DIRECTORY_SEPARATOR . $base, SCANDIR_SORT_ASCENDING);
        foreach ($dir as $file) {
            $path = "{$this->sourceRoot}$base$file";
            if (array_reduce(
                $this->metaData['excluded_paths'],
                function ($carry, $item) use ($path) {
                    return $carry | fnmatch($item, $path, FNM_NOESCAPE);
                }, false)
            ) { continue; }
            if (is_dir($path) && $recursive) {
                $files = array_merge($files, $this->getFiles($path, true));
            } else if (!is_dir($path)) {
                $files[] = "$base$file";
            }
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
}
