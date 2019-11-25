<?php


namespace nyansapow\sites;


abstract class AbstractSite
{
    protected $settings;
    private $path;
    private $sourceRoot;
    private $destinationRoot;
    private $data;

    /**
     * @var ContentFactory
     */
    protected $contentFactory;

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

    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    public function getSetting($setting)
    {
        return $this->settings[$setting] ?? null;
    }

    public function setContentFactory(ContentFactory $contentFactory)
    {
        $this->contentFactory = $contentFactory;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    protected function getFiles(string $base = '', bool $recursive = false) : array
    {
        $files = array();
        $dir = scandir("{$this->sourceRoot}/{$this->path}/$base", SCANDIR_SORT_ASCENDING);
        foreach ($dir as $file) {
            $path = "{$this->sourceRoot}" . ($base == '' ? '' : "$base/") . "$file";
            if (array_reduce(
                $this->settings['excluded_paths'],
                function ($carry, $item) use($path) {return $carry | fnmatch($item, $path); },false)
            ) continue;
            if (is_dir($path) && $recursive) {
                $files = array_merge($files, $this->getFiles($path, true));
            } else if (!is_dir($path)) {
                $path = substr($path, strlen(realpath($this->sourceRoot)));
                $files[] = $path;
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

    public abstract function getPages(): array;
    public abstract function getType(): string;
    public abstract function getDefaultTheme(): string;
}
