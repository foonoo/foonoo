<?php

namespace foonoo;

/**
 * Interface for all commands that can be executed through the main foonoo binary.
 *
 * @author ekow
 */
interface CommandInterface {
    public function execute(array $options = []);
}
