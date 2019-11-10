<?php


namespace nyansapow\sites;


abstract class AbstractSite
{
    protected $settings;

    private $sourcePath;
    private $destinationPath;
    private $sourcePathRelativeToRoot;
    private $sourceRoot;
    private $destinationRoot;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    public function getSourcePath() : string
    {
        return $this->sourcePath;
    }

    public function setSourcePath($sourcePath) : void
    {
        $this->sourcePath = $sourcePath;
    }

    public function getSourceRoot() : string
    {
        return $this->sourceRoot;
    }

    public function getSourcePathRelativeToRoot() : string
    {
        if(!$this->sourcePathRelativeToRoot) {
            $this->sourcePathRelativeToRoot = (string)substr($this->sourcePath, strlen($this->sourceRoot));
        }
        return $this->sourcePathRelativeToRoot;
    }

    public function setSourceRoot(string $sourceRoot) : void
    {
        $this->sourceRoot = $sourceRoot;
    }

    public function setDestinationPath(string $destinationPath) : void
    {
        $this->destinationPath = $destinationPath;
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

    public function setPageFactory(PageFactory $pageFactory)
    {
        $this->pageFactory = $pageFactory;
    }

    protected function getFiles($base = '', $recursive = false)
    {
        $files = array();
        $dir = scandir("{$this->sourcePath}/$base", SCANDIR_SORT_ASCENDING);
        foreach ($dir as $file) {
            $path = "{$this->sourcePath}" . ($base == '' ? '' : "$base/") . "$file";
            if (array_reduce(
                $this->settings['excluded_paths'],
                function ($carry, $item) use($path) {return $carry | fnmatch($item, $path); },false)
            ) continue;
            if (is_dir($path) && $recursive) {
                $files = array_merge($files, $this->getFiles($path, true));
            } else if (!is_dir($path)) {
                $path = substr($path, strlen(realpath($this->sourceRoot . $this->getSourcePathRelativeToRoot())));
                $files[] = $path;
            }
        }
        return $files;
    }

    protected function getPathInSource($path)
    {
        return realpath($this->sourceRoot . $this->getSourcePathRelativeToRoot()) . "/" . $path;
    }

    protected function getPathInDestination($path)
    {
        return $this->destinationRoot. $this->getSourcePathRelativeToRoot() . $path;
    }

    public abstract function getPages(): array;

    public abstract function getType(): string;

}
