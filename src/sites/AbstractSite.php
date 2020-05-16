<?php


namespace nyansapow\sites;


use nyansapow\utils\Cache;
use nyansapow\content\AutomaticContentFactory;

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

    /**
     * An instance of the cache holding data for this site.
     * @var Cache
     */
    private $cache;

    /**
     * Used for creating automatic content from files.
     * @var AutomaticContentFactory
     */
    protected $automaticContentFactory;

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
     * @param $setting
     * @return mixed|null
     */
    public function getSetting($setting)
    {
        return $this->metaData[$setting] ?? null;
    }

    public function setAutomaticContentFactory(AutomaticContentFactory $automaticContentFactory)
    {
        $this->automaticContentFactory = $automaticContentFactory;
    }

    public function setTemplateData(array $templateData): void
    {
        $this->templateData = $templateData;
    }

    public function getTemplateData(string $pageDestination = null): array
    {
        if ($pageDestination) {
            return array_merge([
                'home_path' => $this->makeRelativeLocation($pageDestination, $this->destinationRoot),
                'site_path' => $this->makeRelativeLocation($pageDestination, $this->getDestinationPath()),
                'site_name' => $this->settings['name'] ?? '',
                'date' => date('jS F Y')
            ],
                $this->templateData
            );
        }
        return $this->templateData;
    }

    private function makeRelativeLocation($path, $relativeTo)
    {
        // Generate a relative location for the assets
        $dir = substr(preg_replace('#/+#', '/', $path), strlen($relativeTo));
        $relativeLocation = '';
        if ($dir != '' && $dir != '.') {
            $dir .= substr($dir, -1) == '/' ? '' : '/';
            $relativeLocation = str_repeat('../', substr_count($dir, '/') - 1);
        }
        return $relativeLocation;
    }

    protected function getFiles(string $base = '', bool $recursive = false): array
    {
        $files = array();
        $base = $base == '' ? '' : "$base/";
        $dir = scandir("{$this->sourceRoot}{$this->path}/$base", SCANDIR_SORT_ASCENDING);
        foreach ($dir as $file) {
            $path = "{$this->sourceRoot}$base$file";
            if (array_reduce(
                $this->metaData['excluded_paths'],
                function ($carry, $item) use ($path) {
                    return $carry | fnmatch($item, $path);
                }, false)
            ) continue;
            if (is_dir($path) && $recursive) {
                $files = array_merge($files, $this->getFiles($path, true));
            } else if (!is_dir($path)) {
                $files[] = "$base$file";
            }
        }
        return $files;
    }

    public function getSourcePath(string $path = ""): string
    {
        return preg_replace("|/+|", "/", "{$this->sourceRoot}/{$this->path}/{$path}");
    }

    public function getDestinationPath(string $path = ""): string
    {
        return preg_replace("|/+|", "/", "{$this->destinationRoot}/{$this->path}/{$path}");
    }

    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function getCache(): Cache
    {
        return $this->cache;
    }

    public abstract function getPages(): array;

    public abstract function getType(): string;

    public abstract function getDefaultTheme(): string;
}
