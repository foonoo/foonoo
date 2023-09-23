<?php 
    $has_toc = isset($has_toc) && $has_toc === true;
    $has_header = isset($site_title) && $site_title != "";
?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Open+Sans&display=swap" rel="stylesheet">           
        <?= $assets_markup['default']->unescape() ?>
        <title><?= $content_title ?> <?= isset($site_title) ? "- $site_title": "" ?></title>
    </head>
    <body>
        <?php if($has_header): ?>
            <?= $this->partial("header", ['site_title' => $site_title, 'has_toc' => $has_toc, 'site_menu' => $site_menu ?? []]) ?>
        <?php endif; ?>
        <div id="body-wrapper" class="wrapper <?= $has_toc ? "has-toc" : "" ?> <?=$has_header ? "has-header" : "" ?>">
            <?php if($has_toc): ?>
            <!-- Note: The container wraps a fixed content item that stays put while the user scrolls. -->
            <div id="left-toc" class="side-toc-container inactive">
                <div id="sliding-toc" class="side-toc">
                    <?= $this->partial("table_of_contents_tag", ['tree' => $global_toc->u(), 'max_level' => 1, 'destination' => $destination]) ?>
                </div>
                <div id="toc-tab">≫</div>
            </div>                                        
            <?php endif; ?>

            <article id="text-content"><?= $body->u() ?></article>

            <?php if(isset($has_toc) && $has_toc): ?>
            <div id="right-toc" class="side-toc-container">
                <div class="side-toc">
                    <?= $this->partial("table_of_contents_tag", ['tree' => $content_toc->u()]) ?>
                </div>
            </div>                                        
            <?php endif; ?>            
        </div>
    </body>
</html>
