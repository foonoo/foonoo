<ul class="toc-level-<?= $tree[0]['level'] ?>">
    <?php foreach ($tree as $item): ?>
        <li>
            <a href="<?= $item['destination'] ?><?= $item['level'] > 1 ? "#{$item['id']}" : "" ?>"><?= $item['title'] ?></a>
            <?php if(!empty($item['children']->u())): ?><?= $this->partial('table_of_contents_tag', ['tree' => $item['children']]) ?><?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
