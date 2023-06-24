<?php
$composer = require __DIR__ . "/../vendor/autoload.php";

use clearice\argparser\ArgumentParser;
use foonoo\asset_pipeline\AssetPipelineFactory;
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
use foonoo\events\SiteWriteEnded;
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
use foonoo\theming\ThemeManager;
use Symfony\Component\Yaml\Parser;
use ntentan\utils\Text;


$container = new Container();
$container->bind(Composer\Autoload\ClassLoader::class)->to(fn() => $composer);

$container->bind(TemplateRenderer::class)->to(function ($container) {
    /** @var \ntentan\honam\EngineRegistry $engineRegistry */
    $engineRegistry = $container->get(EngineRegistry::class);
    $templateFileResolver = $container->get(TemplateFileResolver::class);
    $templateRenderer = new TemplateRenderer($engineRegistry, $templateFileResolver);
    $engineRegistry->registerEngine(['mustache'], $container->get(MustacheEngineFactory::class));
    $engineRegistry->registerEngine(['tpl.php', 'tplphp'],
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
$container->bind(ThemeManager::class)->to(ThemeManager::class)->asSingleton();

$container->bind(TagParser::class)->to(function ($container) {
    /** @var \foonoo\text\DefaultTags $defaultTags */
    $defaultTags = $container->get(DefaultTags::class);
    $tagParser = new TagParser();
    $regexMap = $defaultTags->getRegexMap();
    foreach ($regexMap as $i => $regex) {
        $tagParser->registerTag($regex['regex'], $i, $regex['callable'], $regex['name']);
    }
    return $tagParser;
})->asSingleton();


$container->bind(AutomaticContentFactory::class)->to(function (Container $container) {
    $registry = new AutomaticContentFactory($container->get(CopiedContentFactory::class));
    $textConverter = $container->get(TextConverter::class);
    $templateEngine = $container->get(TemplateEngine::class);
    
    // Register a markdown factory
    $registry->register(
        function ($params) use ($textConverter) {
            $extension = strtolower(pathinfo($params['source'], PATHINFO_EXTENSION));
            return $textConverter->isConvertible($extension, 'html');
        },
        $container->get(MarkupContentFactory::class)
    );
    
    // Register a templated content factory
    $registry->register(
        function ($params) use ($templateEngine) {
            $extension = strtolower(pathinfo($params['source'], PATHINFO_EXTENSION));
            return file_exists($params['source']) && $templateEngine->isRenderable($extension);
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
})->asSingleton();

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
        function ($args) use ($container) {
            return new SiteWriteStarted(
                $args['site'], $container->get(TemplateEngine::class), $container->get(ThemeManager::class)
            );
        }
    );
    $eventDispatcher->registerEventType(SiteWriteEnded::class,
        function ($args) {
            return new SiteWriteEnded($args['site']);
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
$container->bind(AssetPipelineFactory::class)->asSingleton();
$container->bind(\foonoo\text\TocGenerator::class)->asSingleton();

$parser = new ArgumentParser();
$parser->addCommand(['name' => 'generate', 'help' => 'Generate a static site with sources from a given directory']);
$parser->addCommand(['name' => 'plugins', 'help' => 'List all plugins and the plugin path hierarchy']);
$parser->addCommand(['name' => 'serve', 'help' => 'Run a local server on a the generated static site']);
$parser->addCommand(['name' => 'create', 'help' => 'Create a new site in this location']);

$parser->addOption(['name' => 'debug', 'help' => 'Do not intercept any uncaught exceptions', 'default' => false]);
$parser->addOption([
    'name' => 'plugin-path', 'short_name' => 'P', 'help' => 'Adds Path to the list of plugin paths', 
    'repeats' => true, 'type' => 'string', 'value' => "PATH"
]);
$parser->addOption([
    'short_name' => 'i',
    'name' => 'input',
    'type' => 'string',
    'help' => "specifies where the input files for the site are found.",
    'command' => ['generate', 'serve', 'create']
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
    'help' => 'port on which to listen for connections',
    'default' => '7000',
    'command' => 'serve'
]);
$parser->addOption([
    'short_name' => 'd',
    'name' => 'description',
    'type' => 'string',
    'help' => 'description for the site',
    'command' => 'create'
]);
$parser->addOption([
    'short_name' => 'u',
    'name' => 'url',
    'type' => 'string',
    'help' => 'the url of your site',
    'command' => 'create'
]);
$parser->addOption([
    'short_name' => 't',
    'name' => 'site-title',
    'type' => 'string',
    'help' => 'the title of your site',
    'command' => 'create'
]);
$parser->addOption([
    'short_name' => 'f',
    'name' => 'force',
    'help' => 'force creation and overwrite any existing sites',
    'command' => 'create'
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
