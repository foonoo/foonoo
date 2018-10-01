<?php

namespace nyansapow;

/**
 * Generates the table of contents by analyzing the DOMDocument generated after the page is rendered.
 *
 * @package nyansapow
 */
class TocGenerator
{
    public static $hasToc = false;
    private static $toc;

    /**
     * An instance of the dom document from which the TOC would be generated.
     * @var DomDocument
     */
    private static $dom;

    private static function getTableOfContentsMarkup($toc = null)
    {
        $output = "";
        if ($toc === null) {
            $toc = self::$toc;
        }

        foreach ($toc as $node) {
            $output .= "<li><a href='#{$node['id']}'>{$node['title']}</a>" . self::getTableOfContentsMarkup($node['children']) . "</li>\n";
        }

        return count($toc) > 0 ? "\n<ul class='toc toc-{$toc[0]['level']}'>\n$output\n </ul>\n" : '';
    }

    private static function getTableOfContentsTree($level = 1, $index = 0)
    {
        $tocTree = array();

        $xpath = new \DOMXPath(self::$dom);;
        $nodes = $xpath->query("//h1|//h2|//h3|//h4|//h5|//h6");

        for ($i = $index; $i < $nodes->length; $i++) {
            $nodeId = str_replace(array(" ", "\t"), "-", strtolower($nodes->item($i)->nodeValue));
            $anchor = self::$dom->createElement('a');
            $anchor->setAttribute('name', $nodeId);
            $anchor->setAttribute('class', 'title-anchor');
            $nodes->item($i)->insertBefore($anchor);
            if ($nodes->item($i) && $nodes->item($i)->nodeName == "h{$level}") {
                if ($nodes->item($i + 1) && $nodes->item($i + 1)->nodeName == "h{$level}" || $nodes->item($i + 1) === null) {
                    $tocTree[] = array(
                        'id' => $nodeId,
                        'title' => $nodes->item($i)->nodeValue,
                        'level' => $level,
                        'children' => array()
                    );
                } else if ($nodes->item($i + 1)->nodeName == "h" . ($level - 1)) {
                    $tocTree[] = array(
                        'id' => $nodeId,
                        'title' => $nodes->item($i)->nodeValue,
                        'level' => $level,
                        'children' => array()
                    );
                    break;
                } else {
                    $children = self::getTableOfContentsTree($level + 1, $i + 1);
                    $newIndex = $children['index'];
                    unset($children['index']);
                    $tocTree[] = array(
                        'id' => $nodeId,
                        'title' => $nodes->item($i)->nodeValue,
                        'level' => $level,
                        'children' => $children
                    );
                    $i = $newIndex;
                }
            } else {
                break;
            }
        }

        if ($level > 1) $tocTree['index'] = $i;

        return $tocTree;
    }

    public static function getTableOfContents()
    {
        return self::$toc;
    }

    public static function domCreated($dom)
    {
        self::$dom = $dom;
        self::$toc = self::getTableOfContentsTree();
    }

    public static function renderTableOfContents()
    {
        return "<div class='toc-wrapper'><span>Contents</span>" . self::getTableOfContentsMarkup() . "</div>";
    }
}
