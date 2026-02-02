<?php

namespace foonoo\commands;

use foonoo\CommandInterface;
use clearice\io\Io;
use ntentan\utils\Filesystem;
use ntentan\utils\exceptions\FileNotFoundException;

/**
 * Implements the serve command.
 * This runs PHP's internal web server.
 *
 * @package nyansapow\commands
 */
class ServeCommand implements CommandInterface
{
    /**
     * Instance of the generate command used to initially build the site before serving it.
     * The generated site is deleted whenever the process is terminated.
     * @var GenerateCommand
     */
    private GenerateCommand $generateCommand;

    /**
     * A path to the directory where the temporary site is stored.
     * @var string
     */
    private string $output;

    /**
     * Instance of clearice IO
     * @var Io
     */
    private $io;

    /**
     * ServeCommand constructor.
     *
     * @param GenerateCommand $generateCommand
     * @param Io $io
     */
    public function __construct(GenerateCommand $generateCommand, Io $io)
    {
        $this->generateCommand = $generateCommand;
        $this->output = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "nyansapow_site";
        $this->io = $io;
    }

    /**
     * Build the site and start the php web server.
     *
     * @param $options
     */
    public function execute(array $options = []): void
    {
        $this->output = Filesystem::getAbsolutePath($options['output'] = $options['output'] ?? $this->output);
        $this->io->output("Generating site to {$options['output']} ...\n");
        $this->generateCommand->execute($options);
        declare(ticks=1)
        if(function_exists("pcntl_signal")) {
            pcntl_signal(SIGINT, fn() => $this->shutdown());
        } else if (function_exists("sapi_windows_set_ctrl_handler")) {
            sapi_windows_set_ctrl_handler(fn() => $this->shutdown(), true);
        }
        $spec = [STDOUT, STDIN, STDERR];
        $pipes = [];
        $this->io->output("Starting the web server ...\n");
        $process = proc_open(
            PHP_BINARY . " -d cli_server.color=1 -S {$options['host']}:{$options['port']} -t {$options['output']}",
            $spec, $pipes
        );
        while (proc_get_status($process)['running']) {
            usleep(500);
        }
    }

    /**
     * Delete the content generated for the site.
     *
     * @throws \ntentan\utils\exceptions\FileNotFoundException
     */
    private function shutdown(): void
    {
        print "\nShutting down ... ";
        try {
            Filesystem::get($this->output)->delete();
            print "OK\n";
        } catch (FileNotFoundException $e) {
            print "Seems the site was already deleted.";  
        }
    }
}
