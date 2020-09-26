<?php


namespace foonoo\utils;

/**
 * A utility trait for classes that need to convert strings between human readable text formats and code friendly
 * labels.
 *
 * @package foonoo\utils
 */
trait Nomenclature
{
    /**
     * Converts a string to a machine friendly label.
     * During the conversion, the text is made lower-case, and every other character except the alpha-numeric ones, the
     * period (.) and the underscore (_) are converted to hyphens. Additionally, an array of already generated IDs from
     * some scope can be presented, so the IDs generated can be unique in that array.
     *
     * @param string $text Any text to be converted
     * @param array $ids An array of already generated IDs within which our new ID must be unique.
     * @return string|string[]|null
     */
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
