<?php


namespace nyansapow\sites;


trait ExtensionAdjuster
{
    protected function adjustFileExtension(string $file, string $targetExtension) : string
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        return $extension ? substr($file, 0, -strlen($extension)) . $targetExtension : $file;
    }
}