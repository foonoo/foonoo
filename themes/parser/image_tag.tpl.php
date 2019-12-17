<figure>
<?php if(count($images) > 0): ?>
<picture>
    <?php foreach($images as $image): ?>
    <source srcset="<?= "{$site_path}np_images/{$image}" ?>" />
    <?php endforeach; ?>
    <img src="<?= "{$site_path}np_images/{$image}" ?>" alt="<?= $alt ?>" <?= $attribute_string ?> />
</picture>
<?php else: ?>
    <img src="<?="{$site_path}np_images/{$image}' alt='{$alt}' $attribute_string" ?>"  />
<?php endif; ?>
    <figcaption> <?= $alt ?></figcaption>
</figure>
