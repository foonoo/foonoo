<?php
require __DIR__ . "/../vendor/autoload.php";

use clearice\argparser\ArgumentParser;
use foonoo\asset_pipeline\AssetPipeline;
use foonoo\asset_pipeline\CSSProcessor;
use foonoo\asset_pipeline\FileProcessor;
use foonoo\asset_pipeline\JSProcessor;
use foonoo\events\ContentLayoutApplied;
use foonoo\events\AllContentsRendered;
use ntentan\honam\EngineRegistry;
use ntentan\honam\engines\php\HelperVariable;
use ntentan\honam\engines\php\Janitor;
use ntentan\honam\factories\MustacheEngineFactory;
use ntentan\honam\factories\PhpEngineFactory;
use ntentan\honam\TemplateFileResolver;
use ntentan\honam\TemplateRenderer;
use ntentan\panie\Container;
use foonoo\events\AssetPipelineReady;
use foonoo\events\ContentWritten;
use foonoo\events\EventDispatcher;
use foonoo\content\AutomaticContentFactory;
use foonoo\events\ContentOutputGenerated;
use foonoo\events\ContentReady;
use foonoo\events\ContentGenerationStarted;
use foonoo\events\PluginsInitialized;
use foonoo\events\SiteObjectCreated;
use foonoo\events\SiteWriteStarted;
use foonoo\events\SiteWritten;
use foonoo\events\ThemeLoaded;
use foonoo\sites\BlogSiteFactory;
use foonoo\content\CopiedContentFactory;
use foonoo\sites\DefaultSiteFactory;
use foonoo\content\MarkupContentFactory;
use foonoo\sites\SiteTypeRegistry;
use foonoo\content\TemplateContentFactory;
use foonoo\text\DefaultTags;
use foonoo\text\MarkdownConverter;
use foonoo\text\TagParser;
use foonoo\text\TemplateEngine;
use foonoo\text\TextConverter;
use Symfony\Component\Yaml\Parser;
use ntentan\utils\Text;

/** @var Container $container */
$container = new Container();

$container->bind(TemplateRenderer::class)->to(function ($container) {
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

$container->bind(EngineRegistry::class)->to(EngineRegistry::class)->asSingleton();
$container->bind(TemplateFileResolver::class)->to(TemplateFileResolver::class)->asSingleton();
$container->bind(EventDispatcher::class)->to(EventDispatcher::class)->asSingleton();
$container->bind(TemplateEngine::class)->to(TemplateEngine::class)->asSingleton();
$container->bind(Parser::class)->to(Parser::class)->asSingleton();

$container->bind(TagParser::class)->to(function ($container) {
    /** @var DefaultTags $defaultTags */
    $defaultTags = $container->get(DefaultTags::class);
    $tagParser = new TagParser();
    $regexMap = $defaultTags->getRegexMap();
    foreach ($regexMap as $regex) {
        $tagParser->registerTag($regex['regex'], $regex['priority'] ?? 0, $regex['callable'], $regex['name']);
    }
    return $tagParser;
})->asSingleton();


$container->bind(AutomaticContentFactory::class)->to(function (Container $container) {
    $registry = new AutomaticContentFactory($container->get(CopiedContentFactory::class));
    $registry->register(
        function ($params) {
            $extension = strtolower(pathinfo($params['source'], PATHINFO_EXTENSION));
            return $extension == 'md';
        },
        $container->get(MarkupContentFactory::class)
    );
    $registry->register(
        function ($params) {
            $extension = strtolower(pathinfo($params['source'], PATHINFO_EXTENSION));
            return file_exists($params['source']) && in_array($extension, ['mustache', 'php']);
        },
        $container->get(TemplateContentFactory::class)
    );
    return $registry;
})->asSingleton();

$container->bind(SiteTypeRegistry::class)->to(function (Container $container) {
    $registry = new SiteTypeRegistry();
    $registry->register($container->get(DefaultSiteFactory::class), 'default');
    $registry->register($container->get(BlogSiteFactory::class), 'blog');
    return $registry;
});

$container->bind(EventDispatcher::class)->to(function (Container $container) {
    $eventDispatcher = new EventDispatcher();
    $eventDispatcher->registerEventType(PluginsInitialized::class,
        function () use ($container) {
            return $container->get(PluginsInitialized::class);
        }
    );
    $eventDispatcher->registerEventType(ThemeLoaded::class,
        function ($args) use ($container) {
            $templateEngine = $container->get(TemplateEngine::class);
            return new ThemeLoaded($args['theme'], $templateEngine);
        }
    );
    $eventDispatcher->registerEventType(ContentOutputGenerated::class,
        function ($args) {
            return new ContentOutputGenerated($args['output'], $args['content'], $args['site']);
        }
    );
    $eventDispatcher->registerEventType(ContentReady::class,
        function ($args) use ($container) {
            $automaticContentFactory = $container->get(AutomaticContentFactory::class);
            return new ContentReady($args['contents'], $automaticContentFactory);
        }
    );
    $eventDispatcher->registerEventType(SiteObjectCreated::class,
        function ($args) {
            return new SiteObjectCreated($args['site']);
        }
    );
    $eventDispatcher->registerEventType(SiteWriteStarted::class,
        function ($args) {
            return new SiteWriteStarted($args['site']);
        }
    );
    $eventDispatcher->registerEventType(SiteWritten::class,
        function ($args) {
            return new SiteWritten($args['site']);
        }
    );
    $eventDispatcher->registerEventType(ContentGenerationStarted::class,
        function ($args) {
            return new ContentGenerationStarted($args['content']);
        }
    );
    $eventDispatcher->registerEventType(AssetPipelineReady::class,
        function ($args) {
            return new AssetPipelineReady($args['pipeline']);
        }
    );
    $eventDispatcher->registerEventType(ContentWritten::class,
        function ($args) {
            return new ContentWritten($args['content'], $args['destination_path']);
        }
    );
    $eventDispatcher->registerEventType(ContentLayoutApplied::class,
        function ($args) {
            return new ContentLayoutApplied($args['output'], $args['content'], $args['site']);
        }
    );
    $eventDispatcher->registerEventType(AllContentsRendered::class,
        function ($args) {
            return new AllContentsRendered($args['site']);
        }
    );
    return $eventDispatcher;
});

$container->bind(TextConverter::class)->to(
    function ($container) {
        $converter = new TextConverter($container->get(TagParser::class));
        $converter->registerConverter('md', 'html', $container->get(MarkdownConverter::class));

        return $converter;
    }
);

$container->bind(AssetPipeline::class)->to(
    function ($container) {
        $pipeline = new AssetPipeline();
        $cssProcessor = $container->get(CSSProcessor::class);
        $jsProcessor = $container->get(JSProcessor::class);
        $pipeline->registerProcessor('css', $cssProcessor);
        $pipeline->registerProcessor('js', $jsProcessor);
        $pipeline->registerProcessor('files', $container->get(FileProcessor::class));
        $pipeline->registerMarkupGenerator('css', $cssProcessor);
        $pipeline->registerMarkupGenerator('js', $jsProcessor);
        return $pipeline;
    }
);
$container->bind(\foonoo\text\TocGenerator::class)->asSingleton();

$parser = new ArgumentParser();
$parser->addCommand(['name' => 'generate', 'help' => 'Generate a static site with sources from a given directory']);
$parser->addCommand(['name' => 'plugins', 'help' => 'list all plugins and the plugin path hierarchy']);
$parser->addCommand(['name' => 'serve', 'help' => 'Run a local server on a the generated static site']);

$parser->addOption(['name' => 'debug', 'help' => 'Do not intercept any uncaught exceptions', 'default' => false]);
$parser->addOption(['name' => 'plugin-path', 'short_name' => 'P', 'help' => 'Adds Path to the list of plugin paths', 'repeats' => true, 'type' => 'string', 'value' => "PATH"]);

$parser->addOption([
    'short_name' => 'i',
    'name' => 'input',
    'type' => 'string',
    'help' => "specifies where the input files for the site are found.",
    'command' => ['generate', 'serve']
]);
$parser->addOption([
    'short_name' => 'o',
    'name' => 'output',
    'type' => 'string',
    "help" => "sets PATH as the output site's root directory",
    'value' => 'PATH',
    'command' => ['generate', 'plugins', 'serve']
]);
$parser->addOption([
    'short_name' => 't',
    'name' => 'site-type',
    'type' => 'string',
    'help' => 'Default site type',
    'default' => 'default',
    'command' => ['generate', 'serve']
]);
$parser->addOption([
    'short_name' => 'n',
    'name' => 'site-name',
    'type' => 'string',
    'help' => 'set the name for the entire site',
    'command' => ['generate', 'serve']
]);

$parser->addOption([
    'short_name' => 'D',
    'name' => 'add-data',
    'type' => 'string',
    'help' => 'pass data to the site in the form [key]:[value]',
    'command' => ['generate', 'serve'],
    'repeats' => true
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
foonoo site generator
$version
EOT;

$parser->enableHelp($description, "Find out more at https://github.com/foonoo");
$options = $parser->parse();

if (!isset($options['__command'])) {
    if (isset($options['__args'][0])) {
        echo "Unknown command `{$options['__args'][0]}`.\nRun `{$options['__executed']} --help` for more information.\n";
    } else {
        echo $parser->getHelpMessage();
    }
    exit(1);
}


$commandClass = sprintf('\foonoo\commands\%sCommand', Text::ucamelize($options['__command']));
$container->resolve($commandClass)->execute($options);
