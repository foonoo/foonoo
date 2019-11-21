<?php


namespace nyansapow\sites;


abstract class AbstractSite
{
    protected $settings;
    private $path;
    private $sourceRoot;
    private $destinationRoot;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    public function setPath(string $path) : void
    {
        $this->path = $path;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function getSourceRoot() : string
    {
        return $this->sourceRoot;
    }

    public function setSourceRoot(string $sourceRoot) : void
    {
        $this->sourceRoot = $sourceRoot;
    }

    public function setDestinationRoot(string $destinationRoot) : void
    {
        $this->destinationRoot = $destinationRoot;
    }

    public function getDestinationRoot() : string
    {
        return $this->destinationRoot;
    }

    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    public function getSetting($setting)
    {
        return $this->settings[$setting] ?? null;
    }

    public function setPageFactory(PageFactory $pageFactory)
    {
        $this->pageFactory = $pageFactory;
    }

    protected function getFiles($base = '', $recursive = false)
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

    public abstract function getPages(): array;
    public abstract function getType(): string;
    public abstract function getDefaultTheme(): string;

}
