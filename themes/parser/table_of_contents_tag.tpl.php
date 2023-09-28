<?php 

/**
Inputs expected by template:

$min_level : The minimum level 
**/

$min_level = $min_level ?? 0;
$max_level = $max_level ?? 10;
$destination = isset($destination) ? $destination->u() : "";

if(count($tree) > 0 && $min_level <= $tree[0]['level'] && $max_level >= $tree[0]['level']):
?>
<ul class="toc-level-<?= $tree[0]['level'] ?>">
    <?php foreach ($tree as $item): 
        // Detect the page being rendered and set it as the active one.
        $active_level = $destination == $item['destination'] || (($is_index ?? false) && $item['title']->u()===$title->u());?>
        <li class="<?= $active_level ? "active" : "inactive" ?>">
            <a href="<?= $item['destination'] ?><?= $item['level'] > 1 ? "#{$item['id']}" : "" ?>"><?= $item['title'] ?></a>
            <?php if(!empty($item['children']->u())) {
                echo $this->partial('table_of_contents_tag', [
                        'tree' => $item['children'], 
                        'min_level' => $min_level, 
                        'max_level' => $active_level ? 10 : $max_level, 
                        'destination' => $destination,
                        'is_index' => $is_index ?? false,
                        'title' => $title ?? null
                    ]); 
            }?>
        </li>
    <?php endforeach; ?>
</ul>
<?php endif ?>
