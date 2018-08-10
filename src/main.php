<?php
require __DIR__ . "/../vendor/autoload.php";

use nyansapow\Nyansapow;
use clearice\argparser\ArgumentParser;
use ntentan\panie\Container;

$parser = new ArgumentParser();
$parser->addOption([
    'short' => 'i',
    'name' => 'input',
    'type' => 'string',
    'help' => "specifies where the input files for the site are found.",
    'required' => true,
]);

$parser->addOption([
    'short' => 'o',
    'name' => 'output',
    'type' => 'string',
    "help" => "specifies where the site should be written to",
    'required' => true
]);

$parser->addOption([
    'short' => 'n',
    'name' => 'site-name',
    'type' => 'string',
    'help' => 'set the name for the entire site'
]);

$version = defined('PHING_BUILD_VERSION') ? "\nVersion " . PHING_BUILD_VERSION : "";
$description = <<<EOT
nyansapow site generator$version

EOT;

echo $description;
$parser->enableHelp($description);
$options = $parser->parse();

if(!isset($options['input'])) {
    echo $parser->getHelpMessage();
    exit();
}

$container = new Container();

try {
    $nyansapow = $container->resolve(Nyansapow::class, ['options' => $options]);
    $nyansapow->write();
} catch(\Exception $e) {
    print $e->getMessage() . "\n";
}
