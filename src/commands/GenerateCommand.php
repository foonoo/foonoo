<?php

namespace nyansapow\commands;

use nyansapow\CommandInterface;
use nyansapow\Nyansapow;

/**
 * Description of GenerateCommand
 *
 * @author ekow
 */
class GenerateCommand implements CommandInterface
{
    private $nyansapow;

    public function __construct(Nyansapow $nyansapow)
    {
        $this->nyansapow = $nyansapow;
    }


    public function execute($options)
    {
        $this->nyansapow->write($options);
    }

}
