<?php

namespace foonoo;

use clearice\io\Io;
use foonoo\events\EventDispatcher;
use foonoo\events\PluginsInitialized;
use ntentan\utils\Text;

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

    /**
     * 
     * @var Io
     */
    private $io;

    public function __construct(EventDispatcher $eventDispatcher, Io $io)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->pluginPaths = [$this->getUserDataDir() . DIRECTORY_SEPARATOR . "foonoo" . DIRECTORY_SEPARATOR . "plugins"];
        $this->io = $io;
    }
    
    private function getUserDataDir() : string
    {
        $operatingSystem = strtolower(php_uname("s"));
        if(substr($operatingSystem, 0, 7) == "windows") {
            return getenv("LOCALAPPDATA");
        } 
        if($operatingSystem == "linux") {
            return getenv('XDG_DATA_HOME') === false ? getenv("HOME") . '/.local/share' : getenv("XDG_DATA_HOME");
        }
        if($operatingSystem == "darwin") {
            return getenv("HOME") . "/Library/Application Support";
        }
        die($operatingSystem);
    }

    /**
     * 
     * @param array $paths
     */
    public function addPluginPaths(array $paths)
    {
        $this->pluginPaths = array_merge(array_map(function ($path) {return realpath($path);}, $paths), $this->pluginPaths);
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
            "Failed to load plugin [$plugin]. The class [$pluginClass] could not be found in path: ["
            . implode($pluginPaths, "; ") . "]"
        );
    }

    public function initializePlugins($plugins, $sitePath) : void
    {
        $this->removePluginEvents();
        if($plugins === null) {
            return;
        }
        $allPluginPaths = array_merge(["{$sitePath}fn_plugins"], $this->pluginPaths);

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

    public function getPluginPaths()
    {
        return $this->pluginPaths;
    }
}
