<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <?= $assets_markup['default']->unescape() ?>
        <title><?= $content_title ?> - <?= $site_title ?></title>
    </head>
    <body>
        <?php if(isset($site_title)): ?><header id="banner"><span id="site-title"><?= $site_title ?></span></header><?php endif; ?>
        <div class="wrapper"><?= $body->u() ?></div>
    </body>
</html>
