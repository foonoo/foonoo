<?php


namespace nyansapow\sites;


use nyansapow\utils\Cache;
use nyansapow\content\AutomaticContentFactory;

abstract class AbstractSite
{
    protected $metaData;
    private $path;
    private $sourceRoot;
    private $destinationRoot;
    private $templateData;
    private $cache;

    /**
     * @var AutomaticContentFactory
     */
    protected $automaticContentFactory;

    public function setPath(string $path) : void
    {
        $this->path = $path;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function setSourceRoot(string $sourceRoot) : void
    {
        $this->sourceRoot = $sourceRoot;
    }

    public function setDestinationRoot(string $destinationRoot) : void
    {
        $this->destinationRoot = $destinationRoot;
    }

    public function setMetaData($metaData)
    {
        $this->metaData = $metaData;
    }

    public function getMetaData()
    {
        return $this->metaData;
    }

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
        if($pageDestination) {
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
        $dir = substr(preg_replace('#/+#','/', $path), strlen($relativeTo));
        $relativeLocation = '';
        if ($dir != '' && $dir != '.') {
            $dir .= substr($dir, -1) == '/' ? '' : '/';
            $relativeLocation = str_repeat('../', substr_count($dir, '/') - 1);
        }
        return $relativeLocation;
    }

    protected function getFiles(string $base = '', bool $recursive = false) : array
    {
        $files = array();
        $base = $base == '' ? '' : "$base/";
        $dir = scandir("{$this->sourceRoot}{$this->path}/$base", SCANDIR_SORT_ASCENDING);
        foreach ($dir as $file) {
            $path = "{$this->sourceRoot}$base$file";
            if (array_reduce(
                $this->metaData['excluded_paths'],
                function ($carry, $item) use($path) {return $carry | fnmatch($item, $path); },false)
            ) continue;
            if (is_dir($path) && $recursive) {
                $files = array_merge($files, $this->getFiles($path, true));
            } else if (!is_dir($path)) {
                $files[] = "$base$file";
            }
        }
        return $files;
    }

    public function getSourcePath(string $path = "") : string
    {
        return preg_replace("|/+|", "/", "{$this->sourceRoot}/{$this->path}/{$path}");
    }

    public function getDestinationPath(string $path = "") : string
    {
        return preg_replace("|/+|", "/", "{$this->destinationRoot}/{$this->path}/{$path}");
    }

    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function getCache() : Cache
    {
        return $this->cache;
    }

    public abstract function getPages(): array;
    public abstract function getType(): string;
    public abstract function getDefaultTheme(): string;
}
