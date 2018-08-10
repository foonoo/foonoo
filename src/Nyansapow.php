<?php

namespace nyansapow;

use ntentan\honam\TemplateEngine;
use clearice\io\Io;

/**
 * The Nyansapow class which represents a nyansapow site. This class performs
 * the task of converting the input files into the output site.
 */
class Nyansapow
{
    private $options;
    private $source;
    private $destination;
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
    private $excludedPaths = array('*.', '*..', "*.gitignore", "*.git", "*/site.ini", "*/site.yml", "*/site.yaml");

    /**
     * Nyansapow constructor.
     * @param Io $io
     * @param $options
     * @throws NyansapowException
     */
    public function __construct(Io $io, $options)
    {
        $this->home = dirname(__DIR__);
        if (!isset($options['source']) || $options['source'] === '') {
            $options['source'] = getcwd();
        }

        if (!file_exists($options['source']) && !is_dir($options['source'])) {
            throw new NyansapowException("Input directory `{$options['source']}` does not exist or is not a directory.");
        }

        if (!isset($options['output']) || $options['output'] === '') {
            $options['output'] = getcwd() . "/output_site";
        }

        $this->excludedPaths[] = realpath($options['output']);
        $this->source = realpath($options['source']) . '/';
        $this->options = $options;
        $this->destination = $options['output'] . '/';
        $this->io = $io;
    }


    public function getDestination()
    {
        return $this->destination;
    }

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
        if (file_exists("{$path}site.ini")) {
            $meta = parse_ini_file("{$path}site.ini");
        } else if (file_exists("{$path}site.yml") || file_exists("{$path}site.yaml")) {
            $parser = new \Symfony\Component\Yaml\Parser();
            $meta = $parser->parse(file_get_contents("{$path}site.yml"));
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
            $sites[$path] = array('type' => 'site');
        }

        while (false !== ($file = $dir->read())) {
            if ($this->isExcluded("{$path}{$file}")) continue;
            if (is_dir("{$path}{$file}")) {
                $sites = array_merge($sites, $this->getSites("{$path}{$file}/"));
            }
        }

        return $sites;
    }

    public function write()
    {
        Processor::setup($this);
        $sites = $this->getSites($this->source, true);
        $this->io->output(sprintf("Found %d site%s in %s\n", count($sites), count($sites) > 1 ? 's' : '', $this->source));

        /**
         * @todo Switch this to ntentan filesystem utilities
         */
        self::mkdir("{$this->destination}/assets/css");
        self::mkdir("{$this->destination}/assets/js");
        self::mkdir("{$this->destination}/assets/fonts");
        self::mkdir("{$this->destination}/assets/images");

        foreach ($sites as $path => $site) {
            $baseDir = (string)substr($path, strlen($this->source));

            /**
             * @todo switch this to ntentan filesystem utilities
             */
            self::copyDir("{$path}np_images", "{$this->destination}$baseDir");
            self::copyDir("{$path}np_assets/*", "{$this->destination}/assets");

            TemplateEngine::reset();
            TemplateEngine::appendPath($this->getHome() . '/themes/global/templates');

            $processor = Processor::get($site, $path);
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

    private static function readData($path)
    {
        $data = [];
        $dir = dir($path);
        $parser = new \Symfony\Component\Yaml\Parser();

        while (false !== ($file = $dir->read())) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($extension === 'yml' || $extension === 'yaml') {
                $data[pathinfo($file, PATHINFO_FILENAME)] = $parser->parse(file_get_contents("$path/$file"));
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
