<?php

namespace nyansapow;

use ntentan\utils\exceptions\FileNotFoundException;
use ntentan\utils\Filesystem;
use clearice\io\Io;
use ntentan\utils\Text;
use nyansapow\events\EventDispatcher;
use nyansapow\events\PluginsInitialized;
use nyansapow\sites\AbstractSite;
use nyansapow\sites\Builder;
use nyansapow\sites\SiteTypeRegistry;
use Symfony\Component\Yaml\Parser as YamlParser;

/**
 * The Nyansapow class which represents a nyansapow site. This class performs
 * the task of converting the input files into the output site.
 */
class Nyansapow
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
     * Base directory where
     * @var string
     */
    private $home;

    /**
     * @var SiteTypeRegistry
     */
    private $siteTypeRegistry;

    /**
     * @var YamlParser
     */
    private $yamlParser;
    /**
     * @var \nyansapow\text\TemplateEngine
     */
    //private $templateEngine;

    private $builder;
    private $eventDispatcher;


    /**
     * Nyansapow constructor.
     * Create an instance of the context object through which Nyansapow works.
     *
     * @param Io $io
     * @param SiteFactory $siteTypeRegistry
     * @param TextProcessors $textProcessors
     * @param \nyansapow\text\TemplateEngine $templateEngine
     */
    public function __construct(Io $io, SiteTypeRegistry $siteTypeRegistry, YamlParser $yamlParser, Builder $builder, EventDispatcher $eventDispatcher)
    {
        $this->io = $io;
        $this->siteTypeRegistry = $siteTypeRegistry;
        $this->yamlParser = $yamlParser;
        $this->builder = $builder;
        $this->eventDispatcher = $eventDispatcher;
    }

    private function readSiteMeta($path)
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
        $metaData = $this->readSiteMeta($path);

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
            $metaData = ['name' => $this->options['site-name'] ?? '', 'type' => $this->options['site-type'] ?? 'plain'];
        }
        $metaData['excluded_paths'] = ['*/.', '*/..', "*/.*", "*/site.yml", "*/site.yaml", $this->options['output'], "*/np_*"]
            + ($metaData['excluded_paths'] ?? []);

        $site = $this->siteTypeRegistry->get($metaData['type'])->create($metaData, $path);

        $site->setPath(substr($path, strlen($this->options['input'])));
        $site->setSourceRoot($this->options['input']);
        $site->setDestinationRoot($this->options['output']);
        $site->setMetaData($metaData);

        return $site;
    }

    private function doSiteWrite()
    {
        $sites = $this->getSites($this->options['input'], true);
        $this->io->output(sprintf("Found %d site%s in \"%s\"\n", count($sites), count($sites) > 1 ? 's' : '', $this->options['input']));
        $this->io->output("Writing all outputs to \"{$this->options['output']}\"\n");

        /** @var AbstractSite $site */
        foreach ($sites as $site) {
            $this->io->output("Generating {$site->getType()} site from \"{$site->getSourcePath()}\"\n");

            if (is_dir($site->getSourcePath("np_images"))) {
                $imageSource = $site->getSourcePath("np_images");
                $imagesDestination = $site->getDestinationPath("np_images");
                try {
                    Filesystem::get($imagesDestination)->delete();
                } catch (FileNotFoundException $e) {}
                $this->io->output("- Copying images from $imageSource to $imagesDestination\n");
                Filesystem::get($imageSource)->copyTo($imagesDestination);
            }

            if (is_dir($site->getSourcePath("np_assets"))) {
                $assetsDestination = $site->getDestinationPath("assets");
                $assetsSource = $site->getSourcePath("np_assets");
                try {
                    Filesystem::get($assetsDestination)->delete();
                } catch (FileNotFoundException $e) {}
                $this->io->output("- Copying assets from $assetsSource to $assetsDestination\n");
                Filesystem::directory($assetsSource)->getFiles()->copyTo($assetsDestination);
            }

            $site->setTemplateData($this->readData($site->getSourcePath("np_data")));
            $this->builder->build($site);
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
            throw new NyansapowException("Input directory `{$options['input']}` does not exist or is not a directory.");
        }

        if (!isset($options['output']) || $options['output'] === '') {
            $options['output'] = 'output_site';
        }

        $options['output'] = Filesystem::getAbsolutePath($options['output']);
        $options['output'] .= $options['output'][-1] == '/' || $options['output'][-1] == '\\' ? '' : DIRECTORY_SEPARATOR;
        $this->builder->setOptions($options);
        $this->options = $options;

    }

    private function initializePlugins(PluginsInitialized $pluginsInitializedEvent)
    {
        $rootSite = $this->readSiteMeta($this->options['input']);
        if(is_array($rootSite) && isset($rootSite['plugins'])) {
            foreach ($rootSite['plugins'] as $plugin) {
                $namespace = dirname($plugin);
                $pluginName = basename($plugin);
                $pluginClassName = Text::ucamelize("${pluginName}") . "Plugin";
                $pluginClass = "\\nyansapow\\plugins\\$namespace\\$pluginName\\$pluginClassName";
                $pluginFile = $this->options['input'] . "/np_plugins/$namespace/$pluginName/$pluginClassName.php";
                require_once $pluginFile;
                $pluginInstance = new $pluginClass([]);
                foreach($pluginInstance->getEvents() as $event => $callable) {
                    $this->eventDispatcher->addListener($event, $callable);
                }
            }
        }
        $this->eventDispatcher->dispatch($pluginsInitializedEvent);
    }

    public function write($options, PluginsInitialized $pluginsInitializedEvent)
    {
        //try {
            $this->setOptions($options);
            $this->initializePlugins($pluginsInitializedEvent);
            $this->doSiteWrite();
//        } catch (Exception $e) {
//            $this->io->error("\n*** Error! Failed to generate site: {$e->getMessage()}.\n");
//            exit(102);
//        }
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
