<?php 

$min_level = $min_level ?? 0;
$max_level = $max_level ?? 10;

if($min_level <= $tree[0]['level'] and $max_level >= $tree[0]['level']):
?>
<ul class="toc-level-<?= $tree[0]['level'] ?>">
    <?php foreach ($tree as $item): ?>
        <li>
            <a href="<?= $item['destination'] ?><?= $item['level'] > 1 ? "#{$item['id']}" : "" ?>"><?= $item['title'] ?></a>
            <?php if(!empty($item['children']->u())) {
                $this->partial('table_of_contents_tag', ['tree' => $item['children'], 'min_level' => $min_level, 'max_level' => $max_level]); 
            }?>
        </li>
    <?php endforeach; ?>
</ul>
<?php endif ?>