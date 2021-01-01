<?php

namespace foonoo;

use clearice\io\Io;
use foonoo\events\EventDispatcher;
use foonoo\events\PluginsInitialized;
use ntentan\utils\Filesystem;
use ntentan\utils\Text;
use XdgBaseDir\Xdg;

/**
 * Loads plugins.
 *
 * @package foonoo
 */
class PluginManager
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $loadedPluginEvents = [];

    private $io;

    public function __construct(EventDispatcher $eventDispatcher, Io $io, Xdg $xdg)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->pluginPaths = [$xdg->getDataDirs()[0] . "/foonoo/plugins"];
        $this->io = $io;
    }

    private function removePluginEvents()
    {
        foreach ($this->loadedPluginEvents as $eventType => $listeners) {
            foreach ($listeners as $listener) {
                $this->eventDispatcher->removeListener($eventType, $listener);
            }
        }
        $this->loadedPluginEvents = [];
    }

    private function getPluginOptions($plugin) : array
    {
        if(is_array($plugin)) {
            $options = reset($plugin);
            $plugin = array_keys($plugin)[0];
        } else {
            $options = [];
        }
        return [$plugin, $options];
    }

    private function getPluginClass(string $plugin, array $options, array $pluginPaths) : Plugin
    {
        $namespace = dirname($plugin);
        $pluginName = basename($plugin);
        $pluginClassName = Text::ucamelize("${pluginName}") . "Plugin";
        $pluginClass = "\\foonoo\\plugins\\$namespace\\$pluginName\\$pluginClassName";
        foreach($pluginPaths as $pluginPath) {
            $pluginFile = "$pluginPath/$namespace/$pluginName/$pluginClassName.php";
            if (file_exists($pluginFile)) {
                require_once $pluginFile;
                return new $pluginClass($plugin, $this->io, $options);
            }
        }
        throw new \Exception(
            "Failed to load plugin [$plugin]. The class [$pluginClass] could not be found in path: "
            . implode($pluginPaths, "; ")
        );
    }

    public function initializePlugins($plugins, $sitePath) : void
    {
        $this->removePluginEvents();
        if($plugins === null) {
            return;
        }
        $allPluginPaths = array_merge($this->pluginPaths, ["{$sitePath}fn_plugins"]);

        foreach ($plugins as $plugin) {
            list($plugin, $options) = $this->getPluginOptions($plugin);
            $pluginInstance = $this->getPluginClass($plugin, $options, $allPluginPaths);
            foreach($pluginInstance->getEvents() as $event => $callable) {
                $id = $this->eventDispatcher->addListener($event, $callable);
                if(!isset($this->loadedPluginEvents[$event])) {
                    $this->loadedPluginEvents[$event] = [];
                }
                $this->loadedPluginEvents[$event][] = $id;
            }
        }
        $this->eventDispatcher->dispatch(PluginsInitialized::class, []);
    }
}
