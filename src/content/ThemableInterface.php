<?php


namespace nyansapow\content;

/**
 * Allows content items to pass data to theme layouts.
 *
 * @package nyansapow\content
 */
interface ThemableInterface
{
    public function getLayoutData();
}