<?php if(count($items) > 0): ?>
<h3><?= $item_type ?></h3>
<?php foreach($items as $item): ?>
<div class="prototype">
    <a name="<?= $item['link'] ?>" class="prototype-anchor"></a>
    <?= t('type_information', $item) ?> <span class="item-name"><?= $item['name'] ?></span> <?php if($postfix) echo t($postfix, $item) ?> <?= $item['value'] != '' || $item['default'] != '' ? "<code> = {$item['value']}{$item['default']}</code>" : '' ?>
</div>
<div class="prototype-description">
<p><?= "{$item['summary']} {$item['details']->u()}" ?></p>
<?= t('sub_type_list', ['items' => $item['parameters'], 'item_title' => 'Parameters']) ?>        
<?= t('sub_type_list', ['items' => $item['throws'], 'item_title' => 'Throws']) ?>        
<?= t('sub_type_list', ['items' => $item['sees'], 'item_title' => 'See Also']) ?>        
</div>
<?php endforeach; ?>
<?php endif; ?>