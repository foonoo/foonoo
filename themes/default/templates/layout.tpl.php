<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <?= $assets_markup['default']->unescape() ?>
        <title><?= $content_title ?> - <?= $site_title ?></title>
    </head>
    <body>
        <?php if(isset($site_title)): ?>
        <div id="banner-wrapper" class="wrapper">
        <header id="banner"><span id="site-title"><?= $site_title ?></span></header>
        </div>
        <?php endif ?>
        <div id="body-wrapper" class="wrapper">
            <article id="body"><?= $body->u() ?></article>
        </div>
    </body>
</html>
