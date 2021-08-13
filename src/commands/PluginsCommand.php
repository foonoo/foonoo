<?php


namespace foonoo\commands;


use clearice\io\Io;
use foonoo\CommandInterface;
use foonoo\PluginManager;

/**
 * This command list all installed plugins.
 */
class PluginsCommand implements CommandInterface
{
    private $pluginManager;
    private $io;

    public function __construct(PluginManager $pluginManager, Io $io)
    {
        $this->pluginManager = $pluginManager;
        $this->io = $io;
    }

    public function execute(array $options = [])
    {
        $hierarchy = array_merge(["[input_path]" . DIRECTORY_SEPARATOR . "_foonoo/plugins"], $this->pluginManager->getPluginPaths());
        $this->io->output("Plugin path hierarchy:\n");
        foreach($hierarchy as $i => $path) {
            $position = $i + 1;
            $this->io->output("  $position. $path\n");
        }
    }
}
