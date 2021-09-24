<?php
namespace foonoo\themes\site;
    
use foonoo\theming\ThemeInterface;
use foonoo\text\TemplateEngine;
use foonoo\asset_pipeline\AssetPipeline;

/**
 * Class for the default site theme.
 */
class SiteTheme implements ThemeInterface
{
    /**
     * Activation call back to inject new stylesheets when colors are changed.
     * 
     * @param TemplateEngine $templateEngine
     * @param AssetPipeline $assetPipeline
     * @param array $options
     * @param array $definition
     */
    public function activated(TemplateEngine $templateEngine, AssetPipeline $assetPipeline, array $options, array &$definition)
    {
        if(isset($options['primary-color']) || isset($options['secondary-color'])) {
            $primaryColor = $options['primary-color'] ?? "#0069d9";
            $secondaryColor = $options['secondary-color'] ?? "#379638";
            $scss = <<< SCSS
            \$primary-color: $primaryColor;
            \$secondary-color: $secondaryColor;

            @import "colors.scss";
            @import "site.scss";
            @import "toc.scss";   
            SCSS;
            $assetPipeline->replaceItem("scss/main.scss", $scss, 'sass', ['base_directory' => "{$definition['path']}/assets/scss"]);
        }
    }

}
