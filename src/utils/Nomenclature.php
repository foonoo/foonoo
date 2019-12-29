<?php


namespace nyansapow\utils;


trait Nomenclature
{
    private function makeId(string $text, array $ids=[])
    {
        $baseId = preg_replace("/([^a-zA-Z0-9\._]+)/", "-", strtolower($text));
        $id = $baseId;
        $counter = 0;
        while(in_array($id, $ids)) {
            $id = $baseId . ($counter++);
        }
        return $id;
    }

    private function makeLabel(string $text)
    {
        return ucfirst(preg_replace("/-+/", " ", $text));
    }
}
