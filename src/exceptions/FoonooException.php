<?php

namespace foonoo\exceptions;

/**
 * Base class for all foonoo exceptions.
 *
 * @author ekow
 */
class FoonooException extends \Exception
{
    private $currentSite;
    
    public function __construct(AbstractSite $site)
    {
        $this->currentSite = $site;
    }
    
    
}
