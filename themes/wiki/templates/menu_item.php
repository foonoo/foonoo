<li><a href="<?= $item['link'] ?>"><?= $item['title'] ?></a><?php
if(count($item['children']))
{
    echo t('menu', ['items' => $item['children'], 'css_classes' => [], 'alias' => '']);
}
?></li>