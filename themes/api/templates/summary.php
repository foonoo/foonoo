<?php if(count($items) > 0): ?>
<h4><?= $item_type ?></h4>
<table>
<?php foreach($items as $item): ?>
    <tr>
        <td><?= t('type_information', $item) ?></td>
        <?php if($split): ?>
        <td><a href="#<?= $item['link'] ?>"><?= $item['name']?></a> <?php if($postfix) echo t($postfix, $item) ?></td>
        <td><?= $item['summary'] ?></td>
        <?php else: ?>
        <td><a href="#<?= $item['link'] ?>"><?= $item['name']?></a> <?php if($postfix) echo t($postfix, $item) ?><div><?= $item['summary'] ?></div></td>
        <?php endif; ?>
    </tr> 
<?php endforeach; ?>
</table>
<?php endif; ?>


