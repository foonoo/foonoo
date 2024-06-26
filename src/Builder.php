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
use foonoo\text\TagParser;

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
     * @var array<string,mixed>
     */
    private array $options;

    /**
     * An instance of clearices I/O class for console I/O.
     * @var Io
     */
    private Io $io;

    /**
     * A registry of all possible site types.
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
    private ?AbstractSite $currentSite = null;
    
    /**
     * An instance of the asset pipeline factory.
     */
    private AssetPipelineFactory $assetPipelineFactory;

    private TagParser $tagParser;


    /**
     * Create an instance of the context object through which Foonoo works.
     *
     * @param Io $io
     * @param SiteTypeRegistry $siteTypeRegistry
     * @param YamlParser $yamlParser
     * @param SiteWriter $builder
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(Io $io, SiteTypeRegistry $siteTypeRegistry, YamlParser $yamlParser, SiteWriter $builder, 
        EventDispatcher $eventDispatcher, AssetPipelineFactory $assetPipelineFactory, TagParser $tagParser)
    {
        $this->io = $io;
        $this->siteTypeRegistry = $siteTypeRegistry;
        $this->yamlParser = $yamlParser;
        $this->siteWriter = $builder;
        $this->eventDispatcher = $eventDispatcher;
        $this->assetPipelineFactory = $assetPipelineFactory;
        $this->tagParser = $tagParser;
    }

    /**
     * @return array<string, mixed> The data from the Yaml site data.
     */
    private function readSiteMetadata(string $path) : array
    {
        $meta = [];
        if (file_exists("{$path}site.yml")) {
            $file = "{$path}site.yml";
            $meta = $this->yamlParser->parse(file_get_contents($file));
        } else if (file_exists("{$path}site.yaml")) {
            $file = "{$path}site.yaml";
            $meta = $this->yamlParser->parse(file_get_contents($file));
        }        
        return $meta ?? [];
    }

    /**
     * @param string $path
     * @param bool $root
     * @return array<array<string, mixed>>
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
            $metaData['excluded_paths'] = [
                    '*/.', '*/..', "*/.*", "*/site.yml", "*/site.yaml", $this->options['output'], "*/_foonoo*"
                ] + ($metaData['excluded_paths'] ?? []);
            
            $sites []= ['meta_data' => $metaData, 'path' => $path] ;//$site;
            while (false !== ($file = $dir->read())) {
                //@todo I feel there's an easier way to accomplish this
                if (array_reduce(
                    $metaData['excluded_paths'], 
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
     * @param array<string,mixed> $metaData Site meta data
     * @param string $path Path to the site
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
    private function buildSites(): void
    {
        $sites = $this->getSites($this->options['input'], true);
        $this->io->output(sprintf("Found %d site%s in \"%s\"\n", count($sites), count($sites) > 1 ? 's' : '', $this->options['input']));
        $this->io->output("Writing all outputs to \"{$this->options['output']}\"\n");

        foreach ($sites as $siteDetails) {
            $this->tagParser->resetTags();
            $this->pluginManager->initializePlugins($siteDetails['meta_data']['plugins'] ?? [], $siteDetails['path']);
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
     * @param array<string,mixed> $options
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
     * @param array<string,mixed> $options A copy of the command line options passed to the script.
     * @param CacheFactory $cacheFactory
     * @param PluginManager $pluginManager
     */
    public function build(array $options, CacheFactory $cacheFactory, PluginManager $pluginManager): void
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
            $siteName = $this->currentSite == null ? "" : $this->currentSite->getType();
            $siteSource = $this->currentSite == null ? "" : " from {$this->currentSite->getSourcePath()}";
            $this->io->error(
                "\n*** The following error occurred while processing the {$siteName} site{$siteSource}:" . 
                "\n{$e->getMessage()}\n");
            exit(102);
        }
    }

    /**
     * Read data from a bunch of YAML or JSON files that could later be injected into pages.
     * 
     * @param string $path
     * @return array<string,mixed>
     */
    private function readData(string $path): array
    {
        $data = [];
        if(!is_dir($path)) {
            return $data;
        }
        $dir = dir($path);
        $extensions = ["json", "yml", "yaml"];
        while (false !== ($file = $dir->read())) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $filename = pathinfo($file, PATHINFO_FILENAME);
            if (array_key_exists($filename, $data) || !in_array($extension, $extensions)) {
                continue;
            }
            $data[$filename] = match($extension) {
                'yml', 'yaml' => $this->yamlParser->parse(file_get_contents("$path/$file")),
                'json' => json_decode(file_get_contents("$path/$file"))
            };
        }
        return $data;
    }
}
