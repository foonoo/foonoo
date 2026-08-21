<?php

namespace foonoo\tests;

use Composer\Autoload\ClassLoader;
use foonoo\asset_pipeline\AssetPipelineFactory;
use foonoo\content\AutomaticContentFactory;
use foonoo\content\CopiedContentFactory;
use foonoo\content\MarkupContentFactory;
use foonoo\content\TemplateContentFactory;
use foonoo\events\AllContentsRendered;
use foonoo\events\AssetPipelineReady;
use foonoo\events\ContentGenerationStarted;
use foonoo\events\ContentLayoutApplied;
use foonoo\events\ContentOutputGenerated;
use foonoo\events\ContentReady;
use foonoo\events\ContentWritten;
use foonoo\events\EventDispatcher;
use foonoo\events\PluginsInitialized;
use foonoo\events\SiteObjectCreated;
use foonoo\events\SiteWriteEnded;
use foonoo\events\SiteWriteStarted;
use foonoo\events\ThemeLoaded;
use foonoo\sites\BlogSiteFactory;
use foonoo\sites\DefaultSiteFactory;
use foonoo\sites\SiteTypeRegistry;
use foonoo\text\DefaultTags;
use foonoo\text\MarkdownConverter;
use foonoo\text\TagParser;
use foonoo\text\TemplateEngine;
use foonoo\text\TextConverter;
use foonoo\text\TocGenerator;
use foonoo\theming\ThemeManager;
use ntentan\honam\EngineRegistry;
use ntentan\honam\engines\php\HelperFactory;
use ntentan\honam\engines\php\HelperVariable;
use ntentan\honam\engines\php\Janitor;
use ntentan\honam\factories\MustacheEngineFactory;
use ntentan\honam\factories\PhpEngineFactory;
use ntentan\honam\TemplateFileResolver;
use ntentan\honam\TemplateRenderer;
use ntentan\panie\Container;
use Symfony\Component\Yaml\Parser;

class TestContainerFactory
{
    /**
     * Create and initialize a test container with standard foonoo bindings.
     *
     * @param array<string, mixed> $customBindings Optional class/interface to instance/callable map
     * @return Container
     */
    public static function create(array $customBindings = []): Container
    {
        $container = new Container();

        $container->bind(TemplateRenderer::class)->to(function ($container) {
            /** @var \ntentan\honam\EngineRegistry $engineRegistry */
            $engineRegistry = $container->get(EngineRegistry::class);
            $templateFileResolver = $container->get(TemplateFileResolver::class);
            $templateRenderer = new TemplateRenderer($engineRegistry, $templateFileResolver);
            $engineRegistry->registerEngine(['mustache'], $container->get(MustacheEngineFactory::class));
            $engineRegistry->registerEngine(
                ['tpl.php', 'tplphp'],
                new PhpEngineFactory(
                    $templateRenderer,
                    new HelperVariable($container->get(HelperFactory::class), $templateRenderer),
                    $container->get(Janitor::class)
                )
            );
            return $templateRenderer;
        })->asSingleton();

        $container->bind(EngineRegistry::class)->to(EngineRegistry::class)->asSingleton();
        $container->bind(TemplateFileResolver::class)->to(TemplateFileResolver::class)->asSingleton();
        $container->bind(TemplateEngine::class)->to(TemplateEngine::class)->asSingleton();
        $container->bind(Parser::class)->to(Parser::class)->asSingleton();
        $container->bind(ThemeManager::class)->to(ThemeManager::class)->asSingleton();
        $container->bind(HelperFactory::class)->to(HelperFactory::class)->asSingleton();
        $container->bind(Janitor::class)->to(Janitor::class)->asSingleton();
        $container->bind(MustacheEngineFactory::class)->to(MustacheEngineFactory::class)->asSingleton();
        $container->bind(AssetPipelineFactory::class)->asSingleton();
        $container->bind(TocGenerator::class)->asSingleton();

        $container->bind(AutomaticContentFactory::class)->to(function (Container $container) {
            $registry = new AutomaticContentFactory($container->get(CopiedContentFactory::class));
            $textConverter = $container->get(TextConverter::class);
            $templateEngine = $container->get(TemplateEngine::class);

            $registry->register(
                function ($params) use ($textConverter) {
                    $extension = strtolower(pathinfo($params['source'], PATHINFO_EXTENSION));
                    return $textConverter->isConvertible($extension, 'html');
                },
                $container->get(MarkupContentFactory::class)
            );

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
            $eventDispatcher->registerEventType(
                PluginsInitialized::class,
                fn() => $container->get(PluginsInitialized::class)
            );
            $eventDispatcher->registerEventType(
                ThemeLoaded::class,
                fn($args) => new ThemeLoaded($args['theme'], $container->get(TemplateEngine::class))
            );
            $eventDispatcher->registerEventType(
                ContentOutputGenerated::class,
                fn($args) => new ContentOutputGenerated($args['output'], $args['content'], $args['site'])
            );
            $eventDispatcher->registerEventType(
                ContentReady::class,
                fn($args) => new ContentReady($args['contents'], $container->get(AutomaticContentFactory::class))
            );
            $eventDispatcher->registerEventType(
                SiteObjectCreated::class,
                fn($args) => new SiteObjectCreated($args['site'])
            );
            $eventDispatcher->registerEventType(
                SiteWriteStarted::class,
                fn($args) => new SiteWriteStarted(
                    $args['site'],
                    $container->get(TemplateEngine::class),
                    $container->get(ThemeManager::class)
                )
            );
            $eventDispatcher->registerEventType(
                SiteWriteEnded::class,
                fn($args) => new SiteWriteEnded($args['site'])
            );
            $eventDispatcher->registerEventType(
                ContentGenerationStarted::class,
                fn($args) => new ContentGenerationStarted($args['content'])
            );
            $eventDispatcher->registerEventType(
                AssetPipelineReady::class,
                fn($args) => new AssetPipelineReady($args['pipeline'])
            );
            $eventDispatcher->registerEventType(
                ContentWritten::class,
                fn($args) => new ContentWritten($args['content'], $args['destination_path'])
            );
            $eventDispatcher->registerEventType(
                ContentLayoutApplied::class,
                fn($args) => new ContentLayoutApplied($args['output'], $args['content'], $args['site'])
            );
            $eventDispatcher->registerEventType(
                AllContentsRendered::class,
                fn($args) => new AllContentsRendered($args['site'])
            );
            return $eventDispatcher;
        })->asSingleton();

        $container->bind(TagParser::class)->to(function (Container $container) {
            $defaultTags = $container->get(DefaultTags::class);
            $tagParser = new TagParser();
            foreach ($defaultTags->getRegexMap() as $priority => $regex) {
                $tagParser->registerTag($regex['regex'], $priority, $regex['callable'], $regex['name']);
            }
            $tagParser->freezeTags();
            return $tagParser;
        });

        $container->bind(TextConverter::class)->to(function (Container $container) {
            $converter = new TextConverter($container->get(TagParser::class));
            $converter->registerConverter('md', 'html', $container->get(MarkdownConverter::class));
            return $converter;
        });

        foreach ($customBindings as $key => $binding) {
            if (is_callable($binding)) {
                $container->bind($key)->to($binding);
            } else {
                $container->bind($key)->to(fn() => $binding);
            }
        }

        return $container;
    }
}
