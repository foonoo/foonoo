<?php
namespace foonoo\themes\site;
    
use foonoo\theming\ThemeInterface;
use foonoo\text\TemplateEngine;
use foonoo\asset_pipeline\AssetPipeline;

class SiteTheme implements ThemeInterface
{
    
    public function activated(TemplateEngine $templateEngine, AssetPipeline $assetPipeline, array $options, array &$definition)
    {
        $assetPipeline->replaceItem(
                "scss/main.scss", "something else", 'css',  
                ['base_directory' => "{$definition['path']}/assets/"]
            );
    }

}
