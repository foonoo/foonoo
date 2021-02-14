<?php

namespace foonoo\text;

use DOMDocument;
use foonoo\events\EventDispatcher;
use foonoo\events\ContentOutputGenerated;
use foonoo\text\TemplateEngine;
use foonoo\utils\Nomenclature;

/**
 * Generates the table of contents from rendered HTML.
 *
 * @package nyansapow
 */
class TocGenerator
{
    use Nomenclature;

    /**
     * Used to map destination paths to their respective ids.
     * @var array
     */
    private $pendingTables = [];

    /**
     * An instance of the template engine/
     * @var \foonoo\text\TemplateEngine
     */
    private $templateEngine;

    public function __construct(EventDispatcher $events, TemplateEngine $templateEngine)
    {
        $events->addListener(ContentOutputGenerated::class, $this->render());
        $this->templateEngine = $templateEngine;
    }

    /**
     * Creates a container DIV for the TOC.
     * This method is called in advance when the [[_TOC_]] tag is encountered. The DIV created by this method acts
     * as a placeholder while the rest of the page is genereted. After all content is ready, the ContentOutputGenerated
     * event registered through the constructor calls the
     *
     * @param $destination
     * @return string
     */
    public function createContainer($destination) : string
    {
        $id = md5($destination);
        $this->pendingTables[$destination] = $id;
        return "<div class='fn-toc' nptoc='$id'/>";
    }

    private function render() : callable
    {
        return function (ContentOutputGenerated $event) {
            $content = $event->getPage();
            $destination = $content->getDestination();
            if(!isset($this->pendingTables[$destination])) {
                return;
            }
            $id = $this->pendingTables[$destination];
            $dom = $event->getDOM();
            $xpath = new \DOMXPath($dom);
            $tree = $this->getTableOfContentsTree($xpath->query("//h2|//h3|//h4|//h5|//h6"));
            $tocContainer = $xpath->query("//div[@nptoc='$id']")->item(0);
            $toc = $dom->createDocumentFragment();
            $toc->appendXML($this->templateEngine->render('table_of_contents_tag', ['tree' => $tree]));
            $tocContainer->appendChild($toc);
            $tocContainer->removeAttribute("nptoc");
        };
    }

    /**
     * Recursively run through the DOM nodes and generate a tree for the TOC.
     *
     * @param \DOMNodeList $nodes
     * @param int $level
     * @param int $index
     * @return array
     */
    private function getTableOfContentsTree(\DOMNodeList $nodes, int $level=2, int $index=0) : array
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
