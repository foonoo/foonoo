<?php

namespace nyansapow\commands;

use nyansapow\CommandInterface;
use clearice\io\Io;
use ntentan\utils\Filesystem;

class ServeCommand implements CommandInterface
{
    private $generateCommand;
    private $output;
    private $io;
    
    public function __construct(GenerateCommand $generateCommand, Io $io)
    {
        $this->generateCommand = $generateCommand;
        $this->output = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "nyansapow_site";
        $this->io = $io;
    }
    
    public function execute($options) 
    {
        $this->output = $options['output'] = $options['output'] ?? $this->output;
        $this->io->output("Generating site to {$options['output']} ...\n");
        $this->generateCommand->execute($options);
        declare(ticks = 1)
        pcntl_signal(SIGINT, [$this, 'shutdown']);
        $spec = [STDOUT, STDIN, STDERR];
        $pipes = [];
        $this->io->output("Starting the web server ...\n");
        $process = proc_open(
            PHP_BINARY . " -d cli_server.color=1 -S {$options['host']}:{$options['port']} -t {$options['output']}", 
            $spec, $pipes
        );
        while(proc_get_status($process)['running']) {
            usleep(500);
        }
        $this->shutdown();        
    }
    
    private function shutdown()
    {
        print "\nShutting down ... ";
        Filesystem::get($this->output)->delete();
        print "OK\n";
    }    
}
