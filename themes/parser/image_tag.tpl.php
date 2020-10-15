<figure>
<?php if(count($images) > 0): ?>
<picture>
    <?php foreach($images as $image): ?>
    <source srcset="<?= "{$site_path}np_images/{$image}" ?>" >
    <?php endforeach; ?>
    <img src="<?= "{$site_path}np_images/{$image}" ?>" alt="<?= $alt ?>" <?= $attribute_string ?> loading="lazy" />
</picture>
<?php else: ?>
    <img src="<?="{$site_path}np_images/{$image}' alt='{$alt}' $attribute_string" ?>" loading="lazy" />
<?php endif; ?>
    <figcaption> <?= $alt ?></figcaption>
</figure>
