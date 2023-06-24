<?php

namespace foonoo;

use clearice\io\Io;
use foonoo\exceptions\FoonooException;
use foonoo\utils\CacheFactory;
use foonoo\events\EventDispatcher;
use foonoo\events\SiteObjectCreated;
use foonoo\sites\AbstractSite;
use foonoo\sites\SiteWriter;
use foonoo\sites\SiteTypeRegistry;
use foonoo\asset_pipeline\AssetPipelineFactory;
use ntentan\utils\Filesystem;
use ntentan\utils\filesystem\File;
use Symfony\Component\Yaml\Parser as YamlParser;
use foonoo\asset_pipeline\AssetPipeline;

/**
 * Builds sites.
 * This class reads the input directory to detect sites, loads all the site generators that are needed, and writes
 * the sites to their output locations.
 */
class Builder
{
    /**
     * Contains default options for the site builder.
     * Most of these options are set directly through the command line arguments. Default values are provided by the
     * command line argument parser.
     */
    private array $options;

    /**
     * @var Io
     */
    private Io $io;

    /**
     * @var SiteTypeRegistry
     */
    private SiteTypeRegistry $siteTypeRegistry;

    /**
     * @var YamlParser
     */
    private YamlParser $yamlParser;

    /**
     * 
     */
    private SiteWriter $siteWriter;

    /**
     * @var EventDispatcher
     */
    private EventDispatcher $eventDispatcher;

    /**
     * @var CacheFactory
     */
    private CacheFactory $cacheFactory;

    /**
     * @var PluginManager
     */
    private PluginManager $pluginManager;
    
    /**
     * An instance of the current site being built.
     * 
     * @var AbstractSite
     */
    private AbstractSite $currentSite;
    
    /**
     * An instance of the asset pipeline factory.
     */
    private AssetPipelineFactory $assetPipelineFactory;


    /**
     * Create an instance of the context object through which Foonoo works.
     *
     * @param Io $io
     * @param SiteTypeRegistry $siteTypeRegistry
     * @param YamlParser $yamlParser
     * @param SiteWriter $builder
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(Io $io, SiteTypeRegistry $siteTypeRegistry, YamlParser $yamlParser, SiteWriter $builder, EventDispatcher $eventDispatcher, AssetPipelineFactory $assetPipelineFactory)
    {
        $this->io = $io;
        $this->siteTypeRegistry = $siteTypeRegistry;
        $this->yamlParser = $yamlParser;
        $this->siteWriter = $builder;
        $this->eventDispatcher = $eventDispatcher;
        $this->assetPipelineFactory = $assetPipelineFactory;
    }

    /**
     * @param $path
     * @return array
     */
    private function readSiteMetadata($path) : array
    {
        $meta = [];
        if (file_exists("{$path}site.yml")) {
            $file = "{$path}site.yml";
            $meta = $this->yamlParser->parse(file_get_contents($file));
        } else if (file_exists("{$path}site.yaml")) {
            $file = "{$path}site.yaml";
            $meta = $this->yamlParser->parse(file_get_contents($file));
        }        
        return $meta;
    }

    /**
     * @param string $path
     * @param bool $root
     * @return array<AbstractSite>
     * @throws FoonooException
     */
    private function getSites(string $path, bool $root = false): array
    {
        // An array to contain all the sites in the root
        $sites = array();
        $dir = dir($path);
        $metaData = $this->readSiteMetadata($path);
        
        // Return sites only if there is a `site.yml` (as determined by a metaData) or its a root site with a metaData.
        if(!empty($metaData) || $root) {
            
            if (empty($metaData)) {
                $metaData = ['name' => $this->options['site-name'] ?? "", 'type' => $this->options['site-type']];
            }
            $metaData['excluded_paths'] = ['*/.', '*/..', "*/.*", "*/site.yml", "*/site.yaml", $this->options['output'], "*/_foonoo*"] + ($metaData['excluded_paths'] ?? []);
            
            //$site = $this->createSite($metaData, $path);
            $sites []= ['meta_data' => $metaData, 'path' => $path] ;//$site;
            while (false !== ($file = $dir->read())) {
                //@todo I feel there's an easier way to accomplish this
                if (array_reduce(
                    $metaData['excluded_paths'] ?? [], //$site->getSetting('excluded_paths'),
                    function ($carry, $item) use ($path, $file) {
                        return $carry | fnmatch($item, "{$path}{$file}", FNM_NOESCAPE);
                    },
                    false)
                ) {
                    continue;
                }
                if (is_dir("{$path}{$file}")) {
                    $sites = array_merge($sites, $this->getSites("{$path}{$file}" . DIRECTORY_SEPARATOR));
                }
            }
        }

        return $sites;
    }

    /**
     * Creates an instance of the AbstractSite for a given site metadata array.
     *
     * @param array $metaData
     * @param string $path
     *
     * @return AbstractSite
     * @throws FoonooException
     */
    private function createSite(array $metaData, string $path): AbstractSite
    {
        if(DIRECTORY_SEPARATOR != "/") {
            $metaData['excluded_paths'] = array_map(
                function($value) { 
                    return str_replace("/", DIRECTORY_SEPARATOR, $value); 
                }, 
                $metaData['excluded_paths']
            );
        }
        $site = $this->siteTypeRegistry->get($metaData['type'])->create();
        $shortPath = substr($path, strlen($this->options['input']));

        $site->setPath($shortPath);
        $site->setSourceRoot($this->options['input']);
        $site->setDestinationRoot($this->options['output']);
        $site->setMetaData($metaData);
        $cacheDir = "{$this->options['input']}{$shortPath}_foonoo/cache";
        Filesystem::directory($cacheDir)->createIfNotExists(true);
        $site->setCache($this->cacheFactory->create($cacheDir));
        $this->eventDispatcher->dispatch(SiteObjectCreated::class, ['site' => $site]);

        return $site;
    }

    /**
     * @throws FoonooException
     * @throws \ntentan\utils\exceptions\FileAlreadyExistsException
     * @throws \ntentan\utils\exceptions\FileNotFoundException
     * @throws \ntentan\utils\exceptions\FileNotReadableException
     * @throws \ntentan\utils\exceptions\FileNotWriteableException
     * @throws \ntentan\utils\exceptions\FilesystemException
     */
    private function buildSites()
    {
        $sites = $this->getSites($this->options['input'], true);
        $this->io->output(sprintf("Found %d site%s in \"%s\"\n", count($sites), count($sites) > 1 ? 's' : '', $this->options['input']));
        $this->io->output("Writing all outputs to \"{$this->options['output']}\"\n");

        foreach ($sites as $siteDetails) {
            $this->pluginManager->initializePlugins($siteDetails['meta_data']['plugins'] ?? null, $siteDetails['path']);
            $site = $this->createSite($siteDetails['meta_data'], $siteDetails['path']);
            $site->setAssetPipeline($this->assetPipelineFactory->create());
            $this->io->output("\nGenerating {$site->getType()} site from \"{$site->getSourcePath()}\"\n");
            $this->currentSite = $site;
            $site->setTemplateData($this->readData($site->getSourcePath("_foonoo/data")));
            $this->siteWriter->write($site);

            if (is_dir($site->getSourcePath("_foonoo/images")) && is_dir($site->getDestinationPath())) {
                $imageSource = $site->getSourcePath("_foonoo/images");
                $imagesDestination = $site->getDestinationPath("images");
                $this->io->output("- Copying images from $imageSource to $imagesDestination\n");
                Filesystem::get($imageSource)->copyTo($imagesDestination, File::OVERWRITE_OLDER);                    
            }

            if (is_dir($site->getSourcePath("_foonoo/public")) && is_dir($site->getDestinationPath())) {
                $assetsDestination = $site->getDestinationPath("public");
                $assetsSource = $site->getSourcePath("_foonoo/public");
                $this->io->output("- Copying public files from $assetsSource to $assetsDestination\n");
                Filesystem::directory($assetsSource)->getFiles()->copyTo($assetsDestination, File::OVERWRITE_OLDER);                    
            }
            $this->io->output("- Done generating {$site->getType()}.\n");

        }
    }

    /**
     * @param $options
     * @throws FoonooException
     */
    private function setOptions(array $options): void
    {
        if (!isset($options['input']) || $options['input'] === '') {
            $options['input'] = getcwd();
        } else {
            FileSystem::checkExists($options['input'], "Failed to open input path [{$options['input']}]");
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
        $this->options = $options;
    }

    /**
     * 
     * 
     * @param array $options A copy of the command line options passed to the script.
     * @param CacheFactory $cacheFactory
     * @param PluginManager $pluginManager
     * @throws FoonooException
     * @throws \ntentan\utils\exceptions\FileAlreadyExistsException
     * @throws \ntentan\utils\exceptions\FileNotFoundException
     * @throws \ntentan\utils\exceptions\FileNotReadableException
     * @throws \ntentan\utils\exceptions\FileNotWriteableException
     * @throws \ntentan\utils\exceptions\FilesystemException
     */
    public function build(array $options, CacheFactory $cacheFactory, PluginManager $pluginManager)
    {
        try {
            $startTime = hrtime(true);
            $this->cacheFactory = $cacheFactory;
            $this->pluginManager = $pluginManager;
            $this->setOptions($options);
            $this->buildSites();
            $duration = hrtime(true) - $startTime;
            $this->io->output(sprintf("Total build time: %.02fs\n", $duration / 1e+9));
        } catch (\Exception $e) {
            if ($options['debug']) {
                throw $e;
            }
            $this->io->error(
                "\n*** The following error occured while processing " .
                (
                    $this->currentSite === null ? 
                    "site" : "the {$this->currentSite->getType()} site from \"{$this->currentSite->getSourcePath()}\"") . 
                ":\n{$e->getMessage()}\n");
            exit(102);
        }
    }

    /**
     * Read data from a bunch of YAML files and put them into a single array.
     * 
     * @param $path
     * @return array
     */
    private function readData($path): array
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
