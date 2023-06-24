<?php


namespace foonoo\content;

/**
 * Allows content items to pass data to theme layouts.
 */
interface ThemableInterface
{
    public function getLayoutData();
}