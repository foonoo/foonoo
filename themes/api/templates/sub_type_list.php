<?php if(count($items)): ?>
    <div class="subheader"><?= $item_title ?></div>
    <table class="subheader-table">
        <?php foreach($items as $item): ?>
        <tr>
            <td><?= t('type_link', $item['type']) ?> <?= $item['name'] ?></td>
            <?php if(isset($item['description'])):?><td><?= $item['description']->u() ?></td><?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>  
