<?php if($attributes['figure'] == "true"): ?> <figure> <?php endif; ?>
<?php
$alt = htmlspecialchars($attributes['alt'] ?? $attributes['__default']);
if($alt != ""){
    $altString = "alt=\"{$alt}\"";
} else {
    $altString = "";
}
?>
<?php if(count($images->unescape()) > 1): ?>
<picture>
    <?php foreach($images as $image): ?>
    <source srcset="<?= "{$site_path}np_images/{$image}" ?>" >
    <?php endforeach; ?>
    <img src="<?= "{$site_path}np_images/{$image}" ?>" <?= $altString ?>/>
</picture>
<?php else: ?>
    <img src="<?="{$site_path}np_images/{$images[0]}" ?>" <?= $altString ?>/>
<?php endif; ?>
<?php if($attributes['figure'] == true): ?>
    <figcaption> <?= $alt ?></figcaption>
</figure>
<?php endif; ?>
