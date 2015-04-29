<?php if(count($items)): ?>
    <b><?= $type ?></b>
    <ul><?php foreach($items as $item): ?><li><a href="<?= $site_path.$item['name'] ?>.html"><?= $item['name'] ?></a></li><?php endforeach; ?></ul>
<?php endif; ?>