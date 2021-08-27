<?php

namespace foonoo\exceptions;

use foonoo\sites\AbstractSite;

/**
 * Base class for all foonoo exceptions.
 *
 * @author ekow
 */
class FoonooException extends \Exception
{
    private $currentSite;
    
    public function __construct(string $message, AbstractSite $site = null)
    {
        parent::__construct($message);
        $this->currentSite = $site;
    }
    
    
}
