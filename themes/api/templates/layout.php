<!doctype html>
<html lang="en">
    <head>
        <title>API</title>
        <link rel="stylesheet" type="text/css" href="<?= $home_path ?>assets/css/api.css" />
    </head>
    <body>
        <div id="header">
            <h1><?= $site_name ?></h1>
        </div>
        <div id="side">
            <div id="namespaces-list">
                <ul><?php foreach($namespaces as $namespace): ?><li><a href="<?= $site_path . $namespace['path'] ?>/index.html">\<?= $namespace['name'] ?></a></li><?php endforeach; ?></ul>
            </div>
            <div id="namespace-items-list">
                <?= t('side_items', array('items' => $classes, 'type' => 'Classes')) ?>
                <?= t('side_items', array('items' => $interfaces, 'type' => 'Interfaces')) ?>
            </div>
        </div><div id="body">
            <?= $body->unescape() ?>
        </div>
        <div id="footer"></div>
    </body>
</html>