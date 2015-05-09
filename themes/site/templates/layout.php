<!doctype html>
<html>
    <head>
        <?= 
        $helpers->stylesheets
            ->add(get_asset('highlight/styles/nyansapow.css'))
            ->add(get_asset('css/site.css'))
            ->setCombine(true)->setContext('site').
        $helpers->javascripts
            ->add(get_asset('highlight/highlight.pack.js'))
            ->setCombine(true)
            ->setContext('site')
        ?>
        <script>hljs.initHighlightingOnLoad();</script>
    </head>
    <body><?= $body->u() ?></body>
</html>
<?php
load_asset('fonts/Roboto-Bold.ttf');
load_asset('fonts/Roboto-Light.ttf');
load_asset('fonts/Roboto-Regular.ttf');
?>