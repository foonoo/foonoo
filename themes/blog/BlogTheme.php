<?php

namespace foonoo\themes\blog;

use foonoo\theming\Theme;
use foonoo\asset_pipeline\AssetPipeline;

class BlogTheme extends Theme
{
    public function activate(AssetPipeline $assetPipeline): void
    {
        $options = $this->getOptions();
        if (isset($options['primary-color']) || isset($options['secondary-color'])) {
            $primaryColor = $options['primary-color'] ?? "#0069d9";
            $secondaryColor = $options['secondary-color'] ?? "#379638";
            $scss = <<< SCSS
                \$primary-color: $primaryColor;
                \$secondary-color: $secondaryColor;
                
                @import "reset.scss";
                @import "blog.scss";                
            SCSS;
            $assetPipeline->replaceItem("scss/main.scss", $scss, 'sass', ['base_directory' => "{$this->getPath()}/assets/scss"]);
        }
    }
}
