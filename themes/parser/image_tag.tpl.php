<?php if($attributes['figure'] == "true"): ?> <figure> <?php endif; ?>
<?php if(count($images->unescape()) > 1): ?>
<picture>
    <?php foreach($images as $image): ?>
    <source srcset="<?= "{$site_path}np_images/{$image}" ?>" >
    <?php endforeach; ?>
    <img src="<?= "{$site_path}np_images/{$image}" ?>"/>
</picture>
<?php else: ?>
    <img src="<?="{$site_path}np_images/{$images[0]}" ?>"/>
<?php endif; ?>
<?php if($attributes['figure'] == true): ?>
    <figcaption> <?= $alt ?></figcaption>
</figure>
<?php endif; ?>
