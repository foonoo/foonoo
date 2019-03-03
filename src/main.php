<?php
require __DIR__ . "/../vendor/autoload.php";

use clearice\io\Io;
use nyansapow\Nyansapow;
use clearice\argparser\ArgumentParser;
use ntentan\panie\Container;
use nyansapow\processors\ProcessorFactory;

$parser = new ArgumentParser();
$parser->addCommand(['name' => 'generate', 'help' => 'Generate a static site with sources from a given directory']);
$parser->addOption([
    'short' => 'i',
    'name' => 'input',
    'type' => 'string',
    'help' => "specifies where the input files for the site are found.",
    'command' => 'generate'
]);

$parser->addOption([
    'short' => 'o',
    'name' => 'output',
    'type' => 'string',
    "help" => "specifies where the site should be written to",
    'command' => 'generate'
]);

$parser->addOption([
    'short' => 't',
    'name' => 'site-type',
    'type' => 'string',
    'help' => 'Default site type',
    'default' => 'site',
    'command' => 'generate'
]);

$parser->addOption([
    'short' => 'n',
    'name' => 'site-name',
    'type' => 'string',
    'help' => 'set the name for the entire site',
    'command' => 'generate'
]);

$parser->addCommand(['name' => 'serve', 'help' => 'Run a local server on a the generated static site']);

$version = defined('PHING_BUILD_VERSION') ? "version " . PHING_BUILD_VERSION : "live source version";
$description = <<<EOT
nyansapow site generator
$version
EOT;

$parser->enableHelp($description);
$options = $parser->parse();

if(!isset($options['__command'])) {
    echo $parser->getHelpMessage();
    exit();
}

echo "$description\n\n";
$container = new Container();
$container->bind(Io::class)->to(Io::class)->asSingleton();
$container->bind(Nyansapow::class)->to(Nyansapow::class)->asSingleton();
$container->bind(ProcessorFactory::class)->to(ProcessorFactory::class);

$nyansapow = $container->resolve(Nyansapow::class, ['options' => $options]);
$nyansapow->write();

