<?php

namespace nyansapow;

/**
 * Description of CommandInterface
 *
 * @author ekow
 */
interface CommandInterface {
    public function execute(array $options = []);
}
