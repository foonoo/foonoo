<?php
require __DIR__ . "/../vendor/autoload.php";

use clearice\io\Io;
use ntentan\honam\EngineRegistry;
use ntentan\honam\factories\PhpEngineFactory;
use ntentan\honam\TemplateFileResolver;
use ntentan\honam\TemplateRenderer;
use nyansapow\Nyansapow;
use clearice\argparser\ArgumentParser;
use ntentan\panie\Container;
use nyansapow\text\Parser;
use nyansapow\text\TemplateEngine;

$parser = new ArgumentParser();
$parser->addCommand(['name' => 'generate', 'help' => 'Generate a static site with sources from a given directory']);
$parser->addOption([
    'short_name' => 'i',
    'name' => 'input',
    'type' => 'string',
    'help' => "specifies where the input files for the site are found.",
    'command' => 'generate'
]);

$parser->addOption([
    'short_name' => 'o',
    'name' => 'output',
    'type' => 'string',
    "help" => "specifies where the site should be written to",
    'command' => 'generate'
]);

$parser->addOption([
    'short_name' => 't',
    'name' => 'site-type',
    'type' => 'string',
    'help' => 'Default site type',
    'default' => 'site',
    'command' => 'generate'
]);

$parser->addOption([
    'short_name' => 'n',
    'name' => 'site-name',
    'type' => 'string',
    'help' => 'set the name for the entire site',
    'command' => 'generate'
]);

$parser->addCommand(['name' => 'serve', 'help' => 'Run a local server on a the generated static site']);

$parser->addOption([
    'short_name' => 'i',
    'name' => 'input',
    'type' => 'string',
    'help' => "specifies where the input files for the site are found.",
    'command' => 'serve'
]);

$parser->addOption([
    'short_name' => 'o',
    'name' => 'output',
    'type' => 'string',
    "help" => "specifies where the site should be written to",
    'command' => 'serve'
]);

$parser->addOption([
    'short_name' => 't',
    'name' => 'site-type',
    'type' => 'string',
    'help' => 'Default site type',
    'default' => 'site',
    'command' => 'serve'
]);

$parser->addOption([
    'short_name' => 'n',
    'name' => 'site-name',
    'type' => 'string',
    'help' => 'set the name for the entire site',
    'command' => 'serve'
]);

$parser->addOption([
    'short_name' => 'h',
    'name' => 'host',
    'type' => 'string',
    'help' => 'hostname of interface on which to listen for connections',
    'default' => 'localhost',
    'command' => 'serve'
]);

$parser->addOption([
    'short_name' => 'p',
    'name' => 'port',
    'type' => 'string',
    'help' => 'port on which to listen',
    'default' => '7000',
    'command' => 'serve'
]);

$version = defined('PHING_BUILD_VERSION') ? "version " . PHING_BUILD_VERSION : "live source version";
$description = <<<EOT
nyansapow site generator
$version
EOT;

$parser->enableHelp($description);
$options = $parser->parse();

if(!isset($options['__command'])) {
    
    if(isset($options['__args'][0])) {
        echo "Unknown command `{$options['__args'][0]}`.\nRun `{$options['__executed']} --help` for more information.\n";
    } else {
        echo $parser->getHelpMessage();
        
    }
    exit(1);    
}

$container = new Container();

$container->bind(Io::class)->to(Io::class)->asSingleton();
$container->bind(Nyansapow::class)->to(Nyansapow::class)->asSingleton();
$container->bind(TemplateEngine::class)->to(TemplateEngine::class)->asSingleton();
$container->bind(Parser::class)->to(Parser::class)->asSingleton();
$container->bind(TemplateFileResolver::class)->to(TemplateFileResolver::class)->asSingleton();

$container->bind(EngineRegistry::class)->to(function($container) {
    $object = new EngineRegistry();
    $object->registerEngine(["tpl.php"], new PhpEngineFactory());
    return $object;
});

$commandClass = sprintf('\nyansapow\commands\%sCommand', ucfirst($options['__command']));
$container->resolve($commandClass)->execute($options);
