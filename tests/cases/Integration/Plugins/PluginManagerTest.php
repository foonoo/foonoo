<?php

namespace foonoo\tests\Integration\Plugins;

use Composer\Autoload\ClassLoader;
use clearice\io\Io;
use foonoo\events\EventDispatcher;
use foonoo\events\PluginsInitialized;
use foonoo\events\SiteWriteStarted;
use foonoo\PluginManager;
use foonoo\sites\AbstractSite;
use foonoo\tests\BaseTestCase;

class PluginManagerTest extends BaseTestCase
{
    public function testPluginInitializationAndEventHookExecution(): void
    {
        $siteDir = $this->createVfsDirectory('test_site');

        // Create plugin file
        $pluginCode = <<<'PHP'
<?php
namespace foonoo\plugins\testorg\sample;

use foonoo\Plugin;
use foonoo\events\SiteWriteStarted;

class SamplePlugin extends Plugin
{
    public static bool $wasExecuted = false;

    public function getEvents(): array
    {
        return [
            SiteWriteStarted::class => function(SiteWriteStarted $event) {
                self::$wasExecuted = true;
            }
        ];
    }
}
PHP;

        $this->createVfsFile('test_site/_foonoo/plugins/testorg/sample/SamplePlugin.php', $pluginCode);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->registerEventType(PluginsInitialized::class, fn() => null);
        $eventDispatcher->registerEventType(SiteWriteStarted::class, fn($args) => new SiteWriteStarted($args['site'], $this->createStub(\foonoo\text\TemplateEngine::class), $this->createStub(\foonoo\theming\ThemeManager::class)));

        $io = $this->createStub(Io::class);
        $classLoader = new ClassLoader();

        $pluginManager = new PluginManager($eventDispatcher, $io, $classLoader);
        $pluginManager->initializePlugins(['testorg/sample'], $siteDir . '/');

        $site = $this->createStub(AbstractSite::class);
        
        $this->assertFalse(\foonoo\plugins\testorg\sample\SamplePlugin::$wasExecuted);

        // Dispatch SiteWriteStarted and verify the plugin's listener is invoked
        $eventDispatcher->dispatch(SiteWriteStarted::class, ['site' => $site]);

        $this->assertTrue(\foonoo\plugins\testorg\sample\SamplePlugin::$wasExecuted);
    }
}
