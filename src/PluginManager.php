<?php

namespace foonoo;

use clearice\io\Io;
use foonoo\events\EventDispatcher;
use foonoo\events\PluginsInitialized;
use ntentan\utils\Text;
use Composer\Autoload\ClassLoader;

/**
 * Resolves paths to plugin files, loads classes when needed, and dispatches initial plugin events.
 *
 * @package foonoo
 */
class PluginManager
{
    /**
     * An instance of an event dispatcher.
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * Keeps track of the events of all currently loaded plugins. 
     * @var array
     */
    private $loadedPluginEvents = [];

    /**
     * An instance of the IO channel to be passed on to loaded plugins. 
     * @var Io
     */
    private $io;
    
    private $classLoader;

    private $pluginPaths;

    public function __construct(EventDispatcher $eventDispatcher, Io $io, ClassLoader $classLoader)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->pluginPaths = [$this->getUserDataDir() . DIRECTORY_SEPARATOR . "foonoo" . DIRECTORY_SEPARATOR . "plugins"];
        $this->io = $io;
        $this->classLoader = $classLoader;
    }
    
    /**
     * Returns a path to the users home directory.
     * This is used as the final location in the path hierarchy when checking for plugin instances. Ultimately, plugins
     * that are meant to be globally accessible should be stored in a special subdirectory in this path.
     * 
     * @return string
     */
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
     * Add a path to the plugin path hierarchy.
     * When loading plugins, this class works its way through this hierarcgy to find plugin code.
     * @param array $paths
     */
    public function addPluginPaths(array $paths)
    {
        $this->pluginPaths = array_merge(array_map(function ($path) {return realpath($path);}, $paths), $this->pluginPaths);
    }

    /**
     * Remove the events of all loaded plugins.
     */
    private function removePluginEvents()
    {
        foreach ($this->loadedPluginEvents as $eventType => $listeners) {
            foreach ($listeners as $listener) {
                $this->eventDispatcher->removeListener($eventType, $listener);
            }
        }
        $this->loadedPluginEvents = [];
    }

    /**
     * Retrieve any options the plugin may have been setup with from the site.yml file.
     * 
     * @param mixed $plugin
     * @return array
     */
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

    /**
     * Run through the entire plugin hierarchy path to load classes for a given plugin.
     * Plugin names are in the format [namespace]/[plugin], and these translate into the following class name: 
     * \foonoo\plugins\[namespace]\[plugin]\[Plugin]ClassName.
     * 
     * @param string $plugin
     * @param array $options
     * @param array $pluginPaths
     * @throws \Exception
     * @return Plugin
     */
    private function getPluginClass(string $plugin, array $options, array $pluginPaths) : Plugin
    {
        $namespace = dirname($plugin);
        $pluginName = basename($plugin);
        $pluginClassName = Text::ucamelize("{$pluginName}") . "Plugin";
        $pluginClass = "foonoo\\plugins\\$namespace\\$pluginName\\$pluginClassName";
        foreach($pluginPaths as $pluginPath) {
            $pluginFile = "$pluginPath/$namespace/$pluginName/$pluginClassName.php";
            if (file_exists($pluginFile)) {
                require_once $pluginFile;
                $instance = new $pluginClass($plugin, $this->io, $options);
                $this->classLoader->addPsr4("foonoo\\plugins\\$namespace\\$pluginName\\", "$pluginPath/$namespace/$pluginName/");
                return $instance;
            }
        }
        throw new \Exception(
            "Foonoo failed to load the [$plugin] plugin required by the site. The [$pluginClass], which is expected to ".
            "hold the plugin's code, could not be found in any of the following paths:\n - " . implode("\n - ", $pluginPaths) . 
            "\nYou can also specify your own plugin path with the `-P` tag."
        );
    }

    /**
     * Initialize the plugin subsystem for a site by loading all plugin classes and sending the PluginsInitialized
     * event.
     * 
     * @param mixed $plugins
     * @param string $sitePath
     */
    public function initializePlugins($plugins, $sitePath) : void
    {
        $this->removePluginEvents();
        if($plugins === null) {
            return;
        }
        $allPluginPaths = array_merge(["{$sitePath}_foonoo/plugins"], $this->pluginPaths);

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

    /**
     * Get the plugin paths hierarchy. 
     * 
     * @return string[]|array
     */
    public function getPluginPaths()
    {
        return $this->pluginPaths;
    }
}
