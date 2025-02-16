<?php if($attributes['frame'] == "figure"): ?> <figure> <?php endif; ?>
<?php if($attributes['frame'] == "div"): ?> <div> <?php endif; ?>
<?php
//$alt = htmlspecialchars($attributes['alt'] ?? $attributes['__default'] ?? "");
if($alt != ""){
    $altString = "alt=\"{$alt}\"";
} else {
    $altString = "";
}
$loading = $attributes['loading'] ?? "lazy";
?>
<img src="<?="{$site_path}images/{$image}" ?>" loading="<?= $loading ?>" <?= $altString ?>>
<?php if($attributes['frame'] == "figure"): ?><figcaption> <?= $alt ?></figcaption></figure><?php endif; ?>
<?php if($attributes['frame'] == "div"):?></div><?php endif; ?>
