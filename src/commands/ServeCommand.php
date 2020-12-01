<?php

namespace foonoo\commands;

use foonoo\CommandInterface;
use clearice\io\Io;
use ntentan\utils\Filesystem;

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
    private $generateCommand;

    /**
     * A path to the directory where the temporary site is stored.
     * @var string
     */
    private $output;

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
    public function execute(array $options = [])
    {
        $this->output = Filesystem::getAbsolutePath($options['output'] = $options['output'] ?? $this->output);
        $this->io->output("Generating site to {$options['output']} ...\n");
        $this->generateCommand->execute($options);
        declare(ticks=1)
        pcntl_signal(SIGINT, [$this, 'shutdown']);
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
        $this->shutdown();
    }

    /**
     * Delete the content generated for the site.
     *
     * @throws \ntentan\utils\exceptions\FileNotFoundException
     */
    private function shutdown()
    {
        print "\nShutting down ... ";
        Filesystem::get($this->output)->delete();
        print "OK\n";
    }
}
