<?php

namespace nyansapow;

use ntentan\honam\TemplateEngine;
use ntentan\utils\Filesystem;
use nyansapow\text\Parser as NyansapowParser;
use clearice\io\Io;
use nyansapow\generators\GeneratorFactory;
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
     * @var Io
     */
    private $io;

    /**
     * Base directory where
     * @var string
     */
    private $home;

    /**
     * A YAML parser.
     * @var YamlParser
     */
    private $yamlParser;

    private $processorFactory;
    
    private $parser;

    /**
     * @var array<string>
     */
    private $excludedPaths = [];

    /**
     * Nyansapow constructor.
     * Create an instance of the context object through which Nyansapow works.
     *
     * @param Io $io
     * @param \Symfony\Component\Yaml\Parser $yamlParser
     * @param GeneratorFactory $processorFactory
     * @param NyansapowParser $parser
     */
    public function __construct(Io $io, YamlParser $yamlParser, GeneratorFactory $processorFactory, NyansapowParser $parser)
    {
        $this->home = dirname(__DIR__);
        $this->io = $io;
        $this->yamlParser = $yamlParser;
        $this->parser = $parser;
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
            if (array_reduce(
                $this->excludedPaths,
                function ($carry, $item) use($path, $file) {return $carry | fnmatch($item, "{$path}{$file}"); },
                false)
            ) continue;
            if (is_dir("{$path}{$file}")) {
                $sites = array_merge($sites, $this->getSites("{$path}{$file}/"));
            }
        }

        return $sites;
    }
    
    private function copySiteTemplates($site, $path)
    {
        if (isset($site['templates']) && is_array($site['templates'])) {
            foreach ($site['templates'] as $template) {
                TemplateEngine::prependPath($path . $template);
            }
        } else if (isset($site['templates'])) {
            TemplateEngine::prependPath($path . $site['templates']);
        }
        
        if (is_dir("{$path}np_templates")) {
            TemplateEngine::prependPath("{$path}np_templates");
        }
    }
    
    private function doSiteWrite()
    {
        $sites = $this->getSites($this->source, true);
        $this->io->output(sprintf("Found %d site%s in %s\n", count($sites), count($sites) > 1 ? 's' : '', $this->source));
        $this->io->output("Writing output site to {$this->destination}\n");

        foreach ($sites as $path => $site) {
            $this->io->output("Generating ${site['type']} from $path\n");
            $baseDirectory = (string)substr($path, strlen($this->source));

            $site['base_directory'] = $baseDirectory;
            $site['source'] = $this->source;
            $site['destination'] = $this->destination;
            $site['path'] = $path;
            $site['home_path'] = $this->home;
            $site['excluded_paths'] = $this->excludedPaths;

            if(is_dir("{$path}np_images")) {
                Filesystem::get("{$path}np_images")->copyTo("{$this->destination}$baseDirectory");
            }

            if(is_dir("{$path}np_assets")) {
                Filesystem::get("{$path}np_assets/*", "{$this->destination}$baseDirectory/assets");
            }

            $processor = $this->processorFactory->create($site);
            $this->copySiteTemplates($site, $path);
        
            if (is_dir("{$path}np_data")) {
                $processor->setData(self::readData("{$path}np_data"));
            }
            
            $processor->outputSite();
        }
    }
    
    private function setOptions($options)
    {
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
        $this->excludedPaths = ['*.', '*..', "*.gitignore", "*.git", "*/site.ini", "*/site.yml", "*/site.yaml", realpath($options['output'])];
        $this->source = "${options['input']}/";
        $this->options = $options;
        $this->destination = "${options['output']}/";
    }

    public function write($options)
    {
        //try {
            $this->setOptions($options);
            $this->doSiteWrite();
//        } catch(\Exception $e) {
//            $this->io->error("\n*** Error! Failed to generate site: {$e->getMessage()}.\n");
//            exit(102);
//        }
    }
}
