<!doctype html>
<html lang="en">
    <head>
        <title><?= $title ?> | <?= $site_name ?></title>
        <?= $helpers->stylesheets->add(get_asset('highlight/styles/mono-blue.css'))->add(get_asset('css/api.css'))->setCombine(true)->setContext('api') ?>
        <?= $helpers->javascripts->add(get_asset('js/api.js'))->add(get_asset('highlight/highlight.pack.js'))->setCombine(true)->setContext('api') ?>
        <script type="text/javascript" src="<?= $home_path ?>assets/js/api.js" ></script>
        <script>hljs.initHighlightingOnLoad();</script>
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
