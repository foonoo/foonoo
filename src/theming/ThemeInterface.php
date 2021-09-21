<?php

namespace foonoo\theming;

use foonoo\text\TemplateEngine;
use foonoo\asset_pipeline\AssetPipeline;

interface ThemeInterface
{
    public function activated(TemplateEngine $templateEngine, AssetPipeline $assetPipeline, array $options, array &$definition);
}
