<?php

namespace foonoo\utils;

use DOMDocument;
use foonoo\events\EventDispatcher;
use foonoo\events\PageOutputGenerated;
use foonoo\text\TemplateEngine;

/**
 * Generates the table of contents by analyzing the DOMDocument generated after the page is rendered.
 *
 * @package nyansapow
 */
class TocGenerator
{
    use Nomenclature;

    private $pendingTables = [];
    private $templateEngine;

    public function __construct(EventDispatcher $events, TemplateEngine $templateEngine)
    {
        $events->addListener(PageOutputGenerated::class, $this->getTOCRenderer());
        $this->templateEngine = $templateEngine;
    }

    public function anticipate($destination)
    {
        $id = md5($destination);
        $this->pendingTables[$destination] = $id;
        return "<div nptoc='$id'/>";
    }

    private function getTOCRenderer()
    {
        return function (PageOutputGenerated $event) {
            $content = $event->getPage();
            $destination = $content->getDestination();
            if(!isset($this->pendingTables[$destination])) {
                return;
            }
            $id = $this->pendingTables[$destination];
            $dom = new DOMDocument();
            $dom->loadHTML($content->render());
            $xpath = new \DOMXPath($dom);
            $tree = $this->getTableOfContentsTree($xpath->query("//h2|//h3|//h4|//h5|//h6"));
            $tocContainer = $xpath->query("//div[@nptoc='$id']");
            $toc = $dom->createDocumentFragment();
            $toc->appendXML($this->templateEngine->render('table_of_contents_tag', ['tree' => $tree]));
            $tocContainer->item(0)->appendChild($toc);
            $event->setOutput($dom->saveHTML());
        };
    }

    /**
     *
     *
     * @param \DOMNodeList $nodes
     * @param int $level
     * @param int $index
     * @return array
     */
    private function getTableOfContentsTree(\DOMNodeList $nodes, int $level=2, int $index=0)
    {
        $tocTree = [];

        for ($i = $index; $i < $nodes->length; $i++) {
            $node = $nodes->item($i);
            if ($node && $node->nodeName == "h{$level}") {
                //$headerPath = $node->getNodePath();
                $id = $this->makeId($node->textContent) . "-" . ($i + 1);
                $nextItem = $nodes->item($i + 1);
                $nextItemLevel = (int) (isset($nextItem) ? substr($nextItem->nodeName, -1) : 0);
                $node->setAttribute("id", $id);

                if ($nextItemLevel === $level) { //$nextItem && $nextItem->nodeName == "h{$level}") {
                    // Add node and continue to node on same level
                    $tocTree[] = array(
                        //'header_path' => $headerPath,
                        'id' => $id,
                        'title' => $node->nodeValue,
                        'level' => $level,
                        'children' => array()
                    );
                } else if ($nextItemLevel < $level) {
                    // Add node on current level and exit for node on lower level
                    $tocTree[] = array(
                        'id' => $id,
                        'title' => $node->nodeValue,
                        'level' => $level,
                        'children' => array()
                    );
                    break;
                } else {
                    $children = $this->getTableOfContentsTree($nodes, $nextItemLevel, $i + 1);
                    $newIndex = $children['index'];
                    unset($children['index']);
                    $tocTree[] = array(
                        'id' => $id,
                        'title' => $node->nodeValue,
                        'level' => $level,
                        'children' => $children
                    );
                    $i = $newIndex;
                }
            } else {
                break;
            }
        }

        if ($level > 2) $tocTree['index'] = $i;
        return $tocTree;
    }
}
