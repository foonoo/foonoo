<?php

namespace foonoo;

use ntentan\utils\Filesystem;
use clearice\io\Io;
use ntentan\utils\filesystem\File;
use ntentan\utils\Text;
use foonoo\events\EventDispatcher;
use foonoo\events\PluginsInitialized;
use foonoo\events\SiteCreated;
use foonoo\sites\AbstractSite;
use foonoo\sites\SiteWriter;
use foonoo\sites\SiteTypeRegistry;
use Symfony\Component\Yaml\Parser as YamlParser;

/**
 * Builds sites.
 * This class reads the input directory to detect sites, and it goes ahead to load all the site generators that are
 * needed to build the sites.
 * 
 */
class Builder
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var Io
     */
    private $io;

    /**
     * @var SiteTypeRegistry
     */
    private $siteTypeRegistry;

    /**
     * @var YamlParser
     */
    private $yamlParser;

    /**
     * @var SiteWriter
     */
    private $siteWriter;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    private $cacheFactory;

    private $loadedPluginEvents = [];


    /**
     * Create an instance of the context object through which Nyansapow works.
     *
     * @param Io $io
     * @param SiteTypeRegistry $siteTypeRegistry
     * @param YamlParser $yamlParser
     * @param SiteWriter $builder
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(Io $io, SiteTypeRegistry $siteTypeRegistry, YamlParser $yamlParser, SiteWriter $builder, EventDispatcher $eventDispatcher)
    {
        $this->io = $io;
        $this->siteTypeRegistry = $siteTypeRegistry;
        $this->yamlParser = $yamlParser;
        $this->siteWriter = $builder;
        $this->eventDispatcher = $eventDispatcher;
    }

    private function readSiteMetadata($path)
    {
        $meta = false;
        if (file_exists("{$path}site.yml")) {
            $file = "{$path}site.yml";
            $meta = $this->yamlParser->parse(file_get_contents($file));
        } else if (file_exists("{$path}site.yaml")) {
            $file = "${path}site.yaml";
            $meta = $this->yamlParser->parse(file_get_contents($file));
        }
        return $meta;
    }

    /**
     * @param string $path
     * @param bool $root
     * @return array<AbstractSite>
     */
    private function getSites(string $path, bool $root = false)
    {
        $sites = array();
        $dir = dir($path);
        $metaData = $this->readSiteMetadata($path);

        if(is_array($metaData) || $root) {
            $site = $this->createSite($metaData, $path);
            $sites []= $site;
            while (false !== ($file = $dir->read())) {
                if (array_reduce(
                    $site->getSetting('excluded_paths'),
                    function ($carry, $item) use ($path, $file) {
                        return $carry | fnmatch($item, "{$path}{$file}");
                    },
                    false)
                ) continue;
                if (is_dir("{$path}{$file}")) {
                    $sites = array_merge($sites, $this->getSites("{$path}{$file}/"));
                }
            }
        }

        return $sites;
    }

    private function createSite($metaData, $path)
    {
        if (!is_array($metaData)) {
            $metaData = ['name' => $this->options['site-name'] ?? "", 'type' => $this->options['site-type']];
        }
        $metaData['excluded_paths'] = ['*/.', '*/..', "*/.*", "*/site.yml", "*/site.yaml", $this->options['output'], "*/np_*"]
            + ($metaData['excluded_paths'] ?? []);

        $site = $this->siteTypeRegistry->get($metaData['type'])->create($metaData, $path);
        $shortPath = substr($path, strlen($this->options['input']));

        $site->setPath($shortPath);
        $site->setSourceRoot($this->options['input']);
        $site->setDestinationRoot($this->options['output']);
        $site->setMetaData($metaData);
        $cacheDir = "{$this->options['input']}{$shortPath}np_cache";
        Filesystem::directory($cacheDir)->createIfNotExists();
        $site->setCache($this->cacheFactory->create($cacheDir));
        $this->eventDispatcher->dispatch(SiteCreated::class, ['site' => $site]);

        return $site;
    }

    private function buildSites()
    {
        $sites = $this->getSites($this->options['input'], true);
        $this->io->output(sprintf("Found %d site%s in \"%s\"\n", count($sites), count($sites) > 1 ? 's' : '', $this->options['input']));
        $this->io->output("Writing all outputs to \"{$this->options['output']}\"\n");

        /** @var AbstractSite $site */
        foreach ($sites as $site) {
            $this->io->output("\nGenerating {$site->getType()} site from \"{$site->getSourcePath()}\"\n");
            $site->setTemplateData($this->readData($site->getSourcePath("np_data")));

            $this->initializePlugins($site->getMetaData()['plugins'] ?? null, $site->getSourcePath('np_plugins'));

            $this->siteWriter->write($site);

            if (is_dir($site->getSourcePath("np_images"))) {
                $imageSource = $site->getSourcePath("np_images");
                $imagesDestination = $site->getDestinationPath("np_images");
                $this->io->output("- Copying images from $imageSource to $imagesDestination\n");
                Filesystem::get($imageSource)->copyTo($imagesDestination, File::OVERWRITE_OLDER);
            }

            if (is_dir($site->getSourcePath("np_assets"))) {
                $assetsDestination = $site->getDestinationPath("assets");
                $assetsSource = $site->getSourcePath("np_assets");
                $this->io->output("- Copying assets from $assetsSource to $assetsDestination\n");
                Filesystem::directory($assetsSource)->getFiles()->copyTo($assetsDestination, File::OVERWRITE_OLDER);
            }
        }
    }

    private function setOptions($options)
    {
        if (!isset($options['input']) || $options['input'] === '') {
            $options['input'] = getcwd();
        } else {
            $options['input'] = realpath($options['input']);
        }
        $options['input'] .= ($options['input'][-1] == '/' || $options['input'][-1] == '\\')
            ? '' : DIRECTORY_SEPARATOR;

        if (!file_exists($options['input']) && !is_dir($options['input'])) {
            throw new FoonooException("Input directory `{$options['input']}` does not exist or is not a directory.");
        }

        if (!isset($options['output']) || $options['output'] === '') {
            $options['output'] = 'output_site';
        }

        $options['output'] = Filesystem::getAbsolutePath($options['output']);
        $options['output'] .= $options['output'][-1] == '/' || $options['output'][-1] == '\\' ? '' : DIRECTORY_SEPARATOR;
        $this->siteWriter->setOptions($options);
        $this->options = $options;

    }

    private function removePluginEvents()
    {
        foreach ($this->loadedPluginEvents as $eventType => $listeners) {
            foreach ($listeners as $listener) {
                $this->eventDispatcher->removeListener($eventType, $listener);
            }
        }
        $this->loadedPluginEvents = [];
    }

    private function initializePlugins($plugins, $sitePath) : void
    {
        $this->removePluginEvents();
        if($plugins === null) {
            return;
        }
        foreach ($plugins as $plugin) {
            if(is_array($plugin)) {
                $options = reset($plugin);
                $plugin = array_keys($plugin)[0];
            } else {
                $options = [];
            }
            $namespace = dirname($plugin);
            $pluginName = basename($plugin);
            $pluginClassName = Text::ucamelize("${pluginName}") . "Plugin";
            $pluginClass = "\\foonoo\\plugins\\$namespace\\$pluginName\\$pluginClassName";
            $pluginFile = "$sitePath/$namespace/$pluginName/$pluginClassName.php";
            Filesystem::checkExists($pluginFile, "Failed to load the $pluginName plugin. Could not find [$pluginFile].");
            require_once $pluginFile;
            $pluginInstance = new $pluginClass($plugin, $this->io, $options);
            foreach($pluginInstance->getEvents() as $event => $callable) {
                $id = $this->eventDispatcher->addListener($event, $callable);
                if(!isset($this->loadedPluginEvents[$event])) {
                    $this->loadedPluginEvents[$event] = [];
                }
                $this->loadedPluginEvents[$event][] = $id;
            }
        }
        $this->eventDispatcher->dispatch(PluginsInitialized::class, []);
    }

    public function build($options, CacheFactory $cacheFactory)
    {
        try {
            $this->cacheFactory = $cacheFactory;
            $this->setOptions($options);
            $this->buildSites();
        } catch (\Exception $e) {
            if ($options['debug']) {
                throw $e;
            }
            $this->io->error("Error: {$e->getMessage()}\n");
            exit(102);
        }
    }

    private function readData($path)
    {
        $data = [];
        if(!is_dir($path)) {
            return $data;
        }
        $dir = dir($path);
        while (false !== ($file = $dir->read())) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($extension === 'yml' || $extension === 'yaml') {
                $data[pathinfo($file, PATHINFO_FILENAME)] = $this->yamlParser->parse(file_get_contents("$path/$file"));
            }
        }
        return $data;
    }
}
