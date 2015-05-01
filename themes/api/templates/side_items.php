<?php if(count($items)): ?>
    <span class="section-head"><?= $type ?></span>
    <?php
    $menuItems = [];
    foreach($items as $item)
    {
        $menuItems[] = array(
            'label' => $item['name'],
            'url' => "{$site_path}{$item['path']}.html",
            'id' => str_replace('/', '-', $item['path'])
        );
    }
    ?>
    <?= $helpers->menu($menuItems)->setCurrentUrl($site_path.$path) ?>
<?php endif; ?>