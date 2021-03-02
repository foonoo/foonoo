<?php 
    $has_toc = isset($has_toc) && $has_toc;
    $toc_tag = $has_toc ? 'has-toc' : '';
?><!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <?= $assets_markup['default']->unescape() ?>
        <title><?= $content_title ?> - <?= $site_title ?></title>
    </head>
    <body>
        <?php if(isset($site_title)): ?>
        <div id="banner-container">
            <div id="banner-wrapper" class="wrapper <?= $toc_tag ?>">
                <header id="banner"><span id="site-title"><?= $site_title ?></span></header>
            </div>            
        </div>
        <?php endif ?>
        <div id="body-wrapper" class="wrapper <?= $toc_tag ?>">
            <?php if(isset($has_toc) && $has_toc): ?>
            <?php endif; ?>
            <article id="body"><?= $body->u() ?></article>
        </div>
    </body>
</html>
