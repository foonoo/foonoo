<ul>
    <?php foreach ($tree as $item): ?>
    <li><?= $item['title'] ?></li>
        <?php if(!empty($item['children'])): ?><?= $this->partial('table_of_contents_tag', ['tree' => $item['children']]) ?><?php endif; ?>

    <?php endforeach; ?>
</ul>
