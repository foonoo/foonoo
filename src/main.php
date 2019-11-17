<?php
require __DIR__ . "/../vendor/autoload.php";

use ntentan\honam\EngineRegistry;
use ntentan\honam\engines\php\HelperVariable;
use ntentan\honam\engines\php\Janitor;
use ntentan\honam\factories\MustacheEngineFactory;
use ntentan\honam\factories\PhpEngineFactory;
use ntentan\honam\TemplateFileResolver;
use ntentan\honam\TemplateRenderer;
use clearice\argparser\ArgumentParser;
use ntentan\panie\Container;
use nyansapow\text\TagParser;

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
    'default' => 'plain',
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

/** @var Container $container */
$container = new Container();

$container->bind(TemplateRenderer::class)->to(function ($container){
    /** @var EngineRegistry $engineRegistry */
    $engineRegistry = $container->get(EngineRegistry::class);
    $templateFileResolver = $container->get(TemplateFileResolver::class);
    $templateRenderer = new TemplateRenderer($engineRegistry, $templateFileResolver);
    $engineRegistry->registerEngine(['mustache'], $container->get(MustacheEngineFactory::class));
    $engineRegistry->registerEngine(['tpl.php'],
        new PhpEngineFactory($templateRenderer,
            new HelperVariable($templateRenderer, $container->get(TemplateFileResolver::class)),
            $container->get(Janitor::class)
        ));
    return $templateRenderer;
})->asSingleton();
$container->bind(TagParser::class)->to(TagParser::class)->asSingleton();
$container->bind(TemplateFileResolver::class)->to(TemplateFileResolver::class)->asSingleton();


$commandClass = sprintf('\nyansapow\commands\%sCommand', ucfirst($options['__command']));
$container->resolve($commandClass)->execute($options);
