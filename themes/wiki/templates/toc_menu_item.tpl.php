<li class="<?= $item['fully_matched'] ? ' menu-fully-matched ' : ''?>"><a href="<?= isset($item['url']) ? $item['url'] : "#{$item['id']}" ?>"><?= $item['title'] ?></a><?php
if(count($item['children']))
{
    echo t('menu', ['items' => $item['children'], 'css_classes' => [], 'alias' => 'toc']);
}
?></li>