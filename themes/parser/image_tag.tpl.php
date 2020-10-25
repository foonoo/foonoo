<?php if($attributes['frame'] == "figure"): ?> <figure> <?php endif; ?>
<?php if($attributes['frame'] == "div"): ?> <div> <?php endif; ?>
<?php
$alt = htmlspecialchars($attributes['alt'] ?? $attributes['__default'] ?? "");
if($alt != ""){
    $altString = "alt=\"{$alt}\"";
} else {
    $altString = "";
}
$loading = $attributes['loading'] ?? "lazy";
?>
<?php if(count($images->unescape()) > 1): ?>
<picture>
    <?php foreach($images as $image): ?>
    <source srcset="<?= "{$site_path}np_images/{$image}" ?>" >
    <?php endforeach; ?>
    <img src="<?= "{$site_path}np_images/{$image}" ?>" loading="<?= $loading ?>" <?= $altString ?>/>
</picture>
<?php else: ?>
    <img src="<?="{$site_path}np_images/{$images[0]}" ?>" loading="<?= $loading ?>" <?= $altString ?>/>
<?php endif; ?>
<?php if($attributes['frame'] == "figure"): ?><figcaption> <?= $alt ?></figcaption><?php endif; ?>
<?php if($attributes['frame'] == "div"):?></div><?php endif; ?>
