<!doctype html>
<html lang="en">
    <head>
        <title><?= $title ?> | <?= $site_name ?></title>
        <?php
        $helpers->stylesheets
            ->add(get_asset('highlight/styles/nyansapow.css'))
            ->add(get_asset('css/nyansapow.css'))
            ->add(get_asset("css/$script.css"));
        foreach($np_extra_css as $sheet)
        {
            $helpers->stylesheets->add(get_asset($sheet));
        }
        echo $helpers->stylesheets->setCombine(true)->setContext($context);
        
        $helpers->javascripts
                ->add(get_asset("js/$script.js"))
                ->add(get_asset('highlight/highlight.pack.js'));
        foreach($np_extra_js as $sheet)
        {
            $helpers->javascripts->add(get_asset($script));
        }                
        echo $helpers->javascripts->setCombine(false)->setContext($context) 
        ?>
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