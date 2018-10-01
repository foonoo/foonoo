<!doctype html>
<html lang="en">
    <head>
        <title><?= $title ?> | <?= $site_name ?></title>
        <link type="text/css" rel="stylesheet" href="assets/css/nyansapow.css" />
        <link type="text/css" rel="stylesheet" href="assets/css/wiki.css" />
        <link type="text/css" rel="stylesheet" href="assets/css/wiki_print.css" media="print"/>
    </head>
    <body>
        <?= t('header', array('site_name' => $site_name, 'context' => $context)) ?>
        <?= $body->u() ?>
    </body>
</html>
