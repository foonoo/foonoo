<?php

namespace nyansapow;

use Exception;
use ntentan\utils\exceptions\FileNotFoundException;
use ntentan\utils\Filesystem;
use clearice\io\Io;
use nyansapow\sites\Builder;
use nyansapow\sites\SiteFactory;
use nyansapow\text\TemplateEngine;
use nyansapow\text\TextProcessors;

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

    private $siteFactory;

    /**
     * @var array<string>
     */
    private $excludedPaths = [];
    /**
     * @var TextProcessors
     */
    private $textProcessors;
    /**
     * @var \nyansapow\text\TemplateEngine
     */
    private $templateEngine;

    private $builder;


    /**
     * Nyansapow constructor.
     * Create an instance of the context object through which Nyansapow works.
     *
     * @param Io $io
     * @param SiteFactory $siteFactory
     * @param TextProcessors $textProcessors
     * @param \nyansapow\text\TemplateEngine $templateEngine
     */
    public function __construct(Io $io, SiteFactory $siteFactory, TextProcessors $textProcessors, TemplateEngine $templateEngine, Builder $builder)
    {
        $this->home = dirname(__DIR__);
        $this->io = $io;
        $this->siteFactory = $siteFactory;
        $this->textProcessors = $textProcessors;
        $this->templateEngine = $templateEngine;
        $this->builder = $builder;
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

    private function getSites($path, $root = false)
    {
        $sites = array();
        $dir = dir($path);

        $metaData = $this->readSiteMeta($path)
            ?? ($root ? ['type' => $this->options['site-type'], 'name' => $this->options['site-name']] : []);
        $metaData['excluded_paths'] = array_merge($metaData['excluded_paths'] ?? [], $this->excludedPaths);

        $site = $this->siteFactory->create($metaData['type'] ?? 'plain');
        $site->setSourcePath($path);
        $site->setDestinationPath($path);
        $site->setSourceRoot($this->options['input']);
        $site->setDestinationRoot($this->options['output']);
        $site->setSettings($metaData);
        $sites[]=$site;

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

    private function setupLocalTemplatePaths($site, $path)
    {
        $siteTemplates = $site->getSetting('templates');
        if (is_array($siteTemplates)) {
            foreach ($siteTemplates as $template) {
                $this->templateEngine->prependPath($path . $template);
            }
        } else if ($siteTemplates) {
            $this->templateEngine->prependPath($path . $siteTemplates);
        }

        if (is_dir("{$path}np_templates")) {
            $this->templateEngine->prependPath("{$path}np_templates");
        }
    }

    private function doSiteWrite()
    {
        $sites = $this->getSites($this->options['input'], true);
        $this->io->output(sprintf("Found %d site%s in \"%s\"\n", count($sites), count($sites) > 1 ? 's' : '', $this->options['input']));
        $this->io->output("Writing all outputs to \"{$this->options['output']}\"\n");
        $this->templateEngine->prependPath(__DIR__ . "/../themes/parser");

        foreach ($sites as $site) {
            $siteType = $site->getType();
            $sitePath = $site->getSourcePath();
            $this->io->output("Generating $siteType site from \"$sitePath\"\n");

//            var_dump($baseDirectory, $this->options, $sitePath);
//            $site['base_directory'] = $baseDirectory;
//            $site['source'] = $this->options['input'];
//            $site['destination'] = $this->options['output'];
//            $site['path'] = $sitePath;
//            $site['home_path'] = $this->home;
//            $site['excluded_paths'] = $this->excludedPaths;

            if (is_dir("{$sitePath}np_images")) {
                $imagesDestination = "{$this->options['output']}{$baseDirectory}np_images";
                try {
                    Filesystem::get($imagesDestination)->delete();
                } catch (FileNotFoundException $e) {

                }
                Filesystem::get("{$sitePath}np_images")->copyTo($imagesDestination);
            }

            if (is_dir("{$sitePath}np_assets")) {
                $baseDirectory = $site->getSourcePathRelativeToRoot();
                $assetsDestination = "{$this->options['output']}$baseDirectory/assets";
                try {
                    Filesystem::get($assetsDestination)->delete();
                } catch (FileNotFoundException $e) {

                }
                Filesystem::directory("{$sitePath}np_assets")->getFiles()->copyTo($assetsDestination);
            }

            $this->setupLocalTemplatePaths($site, $sitePath);

            $data = $this->readData("{$sitePath}np_data");

            $this->builder->build($site, $data);
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
        $this->excludedPaths = ['*.', '*..', "*.gitignore", "*.git", "*/site.yml", "*/site.yaml", $options['output']];
        $this->options = $options;

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
