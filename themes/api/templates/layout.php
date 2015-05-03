<!doctype html>
<html lang="en">
    <head>
        <title><?= $title ?> | <?= $site_name ?></title>
        <link rel="stylesheet" type="text/css" href="<?= $home_path ?>assets/css/api.css" />
        <script type="text/javascript" src="<?= $home_path ?>assets/js/api.js" ></script>
    </head>
    <body>
        <div id="header">
            <h1><?= $site_name ?></h1>
        </div>
        <div id="side">
            <div id="namespaces-list">
                <?php 
                $items = [];
                foreach($namespaces as $namespace){
                    $items[] = array(
                        'label' => "{$namespace['label']}",
                        'url' => "{$site_path}{$namespace['path']}index.html",
                        'id' => str_replace('\\', '_', $namespace['name'])
                    );
                }
                echo $helpers->menu($items)->setCurrentUrl($site_path.$namespace_path. 'index.html');
                ?>
            </div>
            <div id="namespace-items-list">
                <?= t('side_items', array('items' => $classes, 'type' => 'Classes', 'site_path' => $site_path, 'path' => $path)) ?>
                <?= t('side_items', array('items' => $interfaces, 'type' => 'Interfaces', 'site_path' => $site_path, 'path' => $path)) ?>
            </div>
        </div><div id="body">
            <div id="body-wrapper"><?= $body->unescape() ?></div>
        </div>
        <div id="footer"></div>
    </body>
</html>