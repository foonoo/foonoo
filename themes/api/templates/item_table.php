<?php if(count($items)): ?>
<h3><?= $type ?></h3>
<table>
    <tbody><?php foreach($items as $item): ?><tr><td><a href="<?= $site_path.$item['path'] ?>.html"><?= $item['name'] ?></a></td><td><?= $item['description'] ?></td></tr><?php endforeach; ?></tbody>
</table>
<?php endif; ?>