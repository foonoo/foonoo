<?php

namespace nyansapow;

use ntentan\honam\TemplateEngine;
use clearice\io\Io;
use nyansapow\processors\ProcessorFactory;
use \Symfony\Component\Yaml\Parser as YamlParser;

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
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $destination;

    /**
     * @var array
     */
    private $pages = array();

    /**
     * @var Io
     */
    private $io;

    /**
     * Base directory where
     * @var string
     */
    private $home;

    private $yamlParser;

    private $processorFactory;

    /**
     * @var array<string>
     */
    private $excludedPaths = array('*.', '*..', "*.gitignore", "*.git", "*/site.ini", "*/site.yml", "*/site.yaml");

    /**
     * Nyansapow constructor.
     * Create an instance of the context object through which Nyansapow works.
     *
     * @param Io $io
     * @param \Symfony\Component\Yaml\Parser $yamlParser
     * @param $options
     * @throws NyansapowException
     */
    public function __construct(Io $io, YamlParser $yamlParser, ProcessorFactory $processorFactory, $options)
    {
        $this->home = dirname(__DIR__);
        if (!isset($options['input']) || $options['input'] === '') {
            $options['input'] = getcwd();
        } else {
            $options['input'] = realpath($options['input']);
        }

        if (!file_exists($options['input']) && !is_dir($options['input'])) {
            throw new NyansapowException("Input directory `{$options['input']}` does not exist or is not a directory.");
        }

        if (!isset($options['output']) || $options['output'] === '') {
            $options['output'] = getcwd() . "/output_site";
        }

        $this->excludedPaths[] = realpath($options['output']);
        $this->source = "${options['input']}/";
        $this->options = $options;
        $this->destination = "${options['output']}/";
        $this->io = $io;
        $this->yamlParser = $yamlParser;
        $this->processorFactory = $processorFactory;
    }

    /**
     * Get the destination where output files are written.
     * 
     * @return string
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * Get the source from which content for sites can be retrieved from.
     * 
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    public function getHome()
    {
        return $this->home;
    }

    public function isExcluded($path)
    {
        foreach ($this->excludedPaths as $excludedPath) {
            if (fnmatch($excludedPath, $path)) {
                return true;
            }
        }

        return false;
    }

    private function readSiteMeta($path)
    {
        $meta = false;
        if (file_exists("{$path}site.yml") || file_exists("{$path}site.yaml")) {
            $meta = $this->yamlParser->parse(file_get_contents("{$path}site.yml"));
        }

        return $meta;
    }

    private function getSites($path, $source = false)
    {
        $sites = array();
        $dir = dir($path);

        $metaData = $this->readSiteMeta($path);

        if (is_array($metaData)) {
            $sites[$path] = $metaData;
        } else if ($metaData === false && $source === true) {
            $sites[$path] = [
                'type' => $this->options['site-type'],
                'name' => $this->options['site-name'] ?? ''
            ];
        }

        while (false !== ($file = $dir->read())) {
            if ($this->isExcluded("{$path}{$file}")) continue;
            if (is_dir("{$path}{$file}")) {
                $sites = array_merge($sites, $this->getSites("{$path}{$file}/"));
            }
        }

        return $sites;
    }

    private function doSiteWrite()
    {
        $sites = $this->getSites($this->source, true);
        $this->io->output(sprintf("Found %d site%s in %s\n", count($sites), count($sites) > 1 ? 's' : '', $this->source));
        $this->io->output("Writing output site to {$this->destination}\n");

        /**
         * @todo Switch this to ntentan filesystem utilities
         */
        self::mkdir("{$this->destination}/assets/css");
        self::mkdir("{$this->destination}/assets/js");
        self::mkdir("{$this->destination}/assets/fonts");
        self::mkdir("{$this->destination}/assets/images");

        foreach ($sites as $path => $site) {
            $this->io->output("Generating ${site['type']} from $path\n");
            $baseDir = (string)substr($path, strlen($this->source));

            /**
             * @todo switch this to ntentan filesystem utilities
             */
            self::copyDir("{$path}np_images", "{$this->destination}$baseDir");
            self::copyDir("{$path}np_assets/*", "{$this->destination}/assets");

            TemplateEngine::reset();

            $processor = $this->processorFactory->create($this, $site, $path);
            $processor->setBaseDir($baseDir);

            if (isset($site['templates']) && is_array($site['templates'])) {
                foreach ($site['templates'] as $template) {
                    TemplateEngine::prependPath($path . $template);
                }
            } else if (isset($site['templates'])) {
                TemplateEngine::prependPath($path . $site['templates']);
            }

            if (is_dir("{$path}np_data")) {
                $processor->setData(self::readData("{$path}np_data"));
            }
            if (is_dir("{$path}np_templates")) {
                TemplateEngine::prependPath("{$path}np_templates");
            }
            $processor->outputSite();
        }
    }

    public function write()
    {
        try {
            $this->doSiteWrite();
        } catch(\Exception $e) {
            $this->io->error("\n*** Error! Failed to generate site: {$e->getMessage()}.\n");
        }        
    }

    public static function copyDir($source, $destination)
    {
        if (!is_dir($destination) && !file_exists($destination)) {
            self::mkdir($destination);
        }

        foreach (glob($source) as $file) {
            $newFile = (is_dir($destination) ? "$destination/" : '') . basename("$file");
            if (is_dir($file)) {
                self::mkdir($newFile);
                self::copyDir("$file/*", $newFile);
            } else {
                copy($file, $newFile);
            }
        }
    }

    private function readData($path)
    {
        $data = [];
        $dir = dir($path);

        while (false !== ($file = $dir->read())) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($extension === 'yml' || $extension === 'yaml') {
                $data[pathinfo($file, PATHINFO_FILENAME)] = $this->yamlParser->parse(file_get_contents("$path/$file"));
            }
        }

        return $data;
    }

    public static function mkdir($path)
    {
        $path = explode('/', $path);
        $dirPath = '';

        foreach ($path as $dir) {
            $dirPath .= "$dir/";
            if (!is_dir($dirPath)) {
                mkdir($dirPath);
            }
        }
    }

    public function getPages()
    {
        return $this->pages;
    }
}
