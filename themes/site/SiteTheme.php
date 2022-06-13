<?php
namespace foonoo\themes\site;
    
use foonoo\theming\Theme;
use foonoo\asset_pipeline\AssetPipeline;

/**
 * Class for the default site theme.
 */
class SiteTheme extends Theme
{
    /**
     * Activation call back to inject new stylesheets when colors are changed.
     * 
     * @param AssetPipeline $assetPipeline
     */
    public function activate(AssetPipeline $assetPipeline)
    {
        $options = $this->getOptions();

        if(isset($options['primary-color']) || isset($options['secondary-color'])) {
            $primaryColor = $options['primary-color'] ?? "#0069d9";
            $secondaryColor = $options['secondary-color'] ?? "#379638";
            $scss = <<< SCSS
                \$primary-color: $primaryColor;
                \$secondary-color: $secondaryColor;
                
                @import "colors.scss";
                @import "site.scss";
                @import "toc.scss";   
                @import "tables.scss";
            SCSS;
            $assetPipeline->replaceItem("scss/main.scss", $scss, 'sass', ['base_directory' => "{$this->getPath()}/assets/scss"]);
        }
    }
}
