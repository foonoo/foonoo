<?php

namespace nyansapow;

use Exception;
use ntentan\utils\exceptions\FileNotFoundException;
use ntentan\utils\Filesystem;
use nyansapow\text\Parser as NyansapowParser;
use clearice\io\Io;
use nyansapow\generators\GeneratorFactory;
use nyansapow\text\TemplateEngine;
use nyansapow\text\TextProcessors;
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

    private $processorFactory;

    /**
     * @var array<string>
     */
    private $excludedPaths = [];
    /**
     * @var TextProcessors
     */
    private $textProcessors;
    /**
     * @var TemplateEngine
     */
    private $templateEngine;


    /**
     * Nyansapow constructor.
     * Create an instance of the context object through which Nyansapow works.
     *
     * @param Io $io
     * @param GeneratorFactory $processorFactory
     * @param TextProcessors $textProcessors
     * @param TemplateEngine $templateEngine
     */
    public function __construct(Io $io, GeneratorFactory $processorFactory, TextProcessors $textProcessors, TemplateEngine $templateEngine)
    {
        $this->home = dirname(__DIR__);
        $this->io = $io;
        $this->processorFactory = $processorFactory;
        $this->textProcessors = $textProcessors;
        $this->templateEngine = $templateEngine;
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
            $meta = $this->textProcessors->parseYaml(file_get_contents($file));
        } else if (file_exists("{$path}site.yaml")) {
            $file = "${path}site.yaml";
            $meta = $this->textProcessors->parseYaml(file_get_contents($file));
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
                function ($carry, $item) use ($path, $file) {
                    return $carry | fnmatch($item, "{$path}{$file}");
                },
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
                $this->templateEngine->prependPath($path . $template);
            }
        } else if (isset($site['templates'])) {
            $this->templateEngine->prependPath($path . $site['templates']);
        }

        if (is_dir("{$path}np_templates")) {
            $this->templateEngine->prependPath("{$path}np_templates");
        }
    }

    private function doSiteWrite()
    {
        $sites = $this->getSites($this->source, true);
        $this->io->output(sprintf("Found %d site%s in %s\n", count($sites), count($sites) > 1 ? 's' : '', $this->source));
        $this->io->output("Writing output site to {$this->destination}\n");
        $this->templateEngine->prependPath(__DIR__ . "/../themes/parser");

        foreach ($sites as $path => $site) {
            $this->io->output("Generating ${site['type']} from $path\n");
            $baseDirectory = (string)substr($path, strlen($this->source));

            $site['base_directory'] = $baseDirectory;
            $site['source'] = $this->source;
            $site['destination'] = $this->destination;
            $site['path'] = $path;
            $site['home_path'] = $this->home;
            $site['excluded_paths'] = $this->excludedPaths;

            if (is_dir("{$path}np_images")) {
                $imagesDestination = "{$this->destination}$baseDirectory/np_images";
                try {
                    Filesystem::get($imagesDestination)->delete();
                } catch (FileNotFoundException $e) {

                }
                Filesystem::get("{$path}np_images")->copyTo($imagesDestination);
            }

            if (is_dir("{$path}np_assets")) {
                $assetsDestination = "{$this->destination}$baseDirectory/assets";
                try {
                    Filesystem::get($assetsDestination)->delete();
                } catch (FileNotFoundException $e) {

                }
                Filesystem::glob("{$path}np_assets/*")->copyTo($assetsDestination);
            }

            $processor = $this->processorFactory->create($site);
            $this->copySiteTemplates($site, $path);

            if (is_dir("{$path}np_data")) {
                $processor->setData($this->readData("{$path}np_data"));
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
//        } catch (Exception $e) {
//            $this->io->error("\n*** Error! Failed to generate site: {$e->getMessage()}.\n");
//            exit(102);
//        }
    }

    private function readData($path)
    {
        $data = [];
        $dir = dir($path);
        while (false !== ($file = $dir->read())) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($extension === 'yml' || $extension === 'yaml') {
                $data[pathinfo($file, PATHINFO_FILENAME)] = $this->textProcessors->parseYaml(file_get_contents("$path/$file"));
            }
        }
        return $data;
    }
}
