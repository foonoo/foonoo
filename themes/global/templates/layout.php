<!doctype html>
<html lang="en">
    <head>
        <title><?= $title ?> | <?= $site_name ?></title>
        <?= $helpers->stylesheets
            ->add(get_asset('highlight/styles/nyansapow.css'))
            ->add(get_asset('css/nyansapow.css'))
            ->add(get_asset("css/$context.css"))
            ->setCombine(true)->setContext($context) .
        
        $helpers->javascripts
                ->add(get_asset("js/$context.js"))
                ->add(get_asset('highlight/highlight.pack.js'))
                ->setCombine(true)
                ->setContext($context) ?>
        <script>hljs.initHighlightingOnLoad();</script>
    </head>
    <body>
        <?= t('header', array('site_name' => $site_name, 'context' => $context)) ?>
        <?= $body->u() ?>
    </body>
</html>
<?php
load_asset('fonts/Roboto-Bold.ttf');
load_asset('fonts/Roboto-Light.ttf');
load_asset('fonts/Roboto-Regular.ttf');
?>