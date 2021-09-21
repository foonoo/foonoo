<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace foonoo\asset_pipeline;

/**
 * Description of SCSSProcessor
 *
 * @author ekow
 */
class SCSSProcessor extends CSSProcessor
{
    public function process(string $path, array $options): array
    {
        $minifier = $this->getMinifier();
        
    }
}
