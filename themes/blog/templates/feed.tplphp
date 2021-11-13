<?php
$items = [];
foreach($posts as $post)
{
    $items[] = [
        'title' => $post['title'],
        'url' => "{$url}/{$post['path']}",
        'summary' => $this->truncate(strip_tags($post['body']), 100),
        'author' => $post['author'],
        'category' => $post['category'],
        'date' => $post['date']
    ];
}
echo $helpers
    ->feed($items)
    ->setTitle($title)
    ->setUrl($url)
    ->setDescription($description);
