<ul>
    <?php foreach ($tree as $item): ?>
        <li><a href="#<?= $item['id'] ?>"><?= $item['title'] ?></a></li>
    <?php if(!empty($item['children']->u())): ?><?= $this->partial('table_of_contents_tag', ['tree' => $item['children']]) ?><?php endif; ?>
    <?php endforeach; ?>
</ul>
