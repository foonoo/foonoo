<?php

namespace nyansapow\utils;

use DOMDocument;
use nyansapow\content\Content;

/**
 * Generates the table of contents by analyzing the DOMDocument generated after the page is rendered.
 *
 * @package nyansapow
 */
class TocGenerator
{
    private $locked = false;

    public function get(Content $content)
    {
        if($this->locked) {
            return;
        }
        $this->locked = true;
        $dom = DOMDocument::loadHTML($content->render());
        $xpath = new \DOMXPath($dom);
        $tree = $this->getTableOfContentsTree($xpath->query("//h1|//h2|//h3|//h4|//h5|//h6"));
        $this->locked = false;
        return $tree;
    }

    /**
     *
     *
     * @param \DOMNodeList $nodes
     * @param int $level
     * @param int $index
     * @return array
     */
    private function getTableOfContentsTree(\DOMNodeList $nodes, int $level = 1, int $index = 0)
    {
        $tocTree = [];

        for ($i = $index; $i < $nodes->length; $i++) {
//            $nodeId = str_replace(array(" ", "\t"), "-", strtolower($nodes->item($i)->nodeValue));
            $headerPath = $nodes->item($i)->getNodePath();
//            $anchor = $this->dom->createElement('a');
//            $anchor->setAttribute('name', $nodeId);
//            $anchor->setAttribute('class', 'title-anchor');
//            $nodes->item($i)->insertBefore($anchor);
            if ($nodes->item($i) && $nodes->item($i)->nodeName == "h{$level}") {
                $nextItem = $nodes->item($i + 1);
                if ($nextItem && $nextItem->nodeName == "h{$level}") { // || $nodes->item($i + 1) === null) {
                    $tocTree[] = array(
                        'header_path' => $headerPath,
                        'title' => $nodes->item($i)->nodeValue,
                        'level' => $level,
                        'children' => array()
                    );
                } else if ($nextItem && $nextItem->nodeName == "h" . ($level - 1)) {
                    $tocTree[] = array(
                        'header_path' => $headerPath,
                        'title' => $nodes->item($i)->nodeValue,
                        'level' => $level,
                        'children' => array()
                    );
                    break;
                } else {
                    $children = $this->getTableOfContentsTree($nodes, $level + 1, $i + 1);
                    $newIndex = $children['index'];
                    unset($children['index']);
                    $tocTree[] = array(
                        'header_path' => $headerPath,
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
}
