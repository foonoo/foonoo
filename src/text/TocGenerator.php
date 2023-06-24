<?php

namespace foonoo\text;

use foonoo\events\EventDispatcher;
use foonoo\events\ContentOutputGenerated;
use foonoo\events\SiteWriteStarted;
use foonoo\utils\Nomenclature;

/**
 * Generates the table of contents and handles the rendering of the [[_TOC_]] tag.
 * 
 * Apart from generating tables of content for inidividual pages, this class can also accumulate all the TOCs generated
 * for use as a global TOC.
 * 
 * Whenever the [[_TOC_]] tag is encountered by the default tag parser, this class is called to create a container. 
 * After any content is rendered, the registered event in this class is then used to replace the the container with
 * an actual table of contents. This delayed process is necessary because all other changes to the content must be made
 * so the TOC generator can capture those, too.
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

    /**
     * Accumulator for the global table of contents.
     * @var array
     */
    private $globalTOC;

    /**
     * A flag that is set when the site wants the global table of contents collected.
     * @var bool
     */
    private $collectTOC;

    public function __construct(EventDispatcher $events, TemplateEngine $templateEngine)
    {
        $events->addListener(ContentOutputGenerated::class, $this->getRenderer());
        $events->addListener(SiteWriteStarted::class, function (SiteWriteStarted $event) {
            $this->globalTOC = [];
            $meta = $event->getSite()->getMetaData();
            $this->collectTOC = (bool)($meta['enable-toc'] ?? false);
        });
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
    public function createContainer($id): string
    {
        $this->pendingTables[] = $id;
        return "<div class='fn-toc' fntoc='$id'/>";
    }

    /**
     * Return the callable function that's used by the text renderer.
     *
     * @return callable
     */
    private function getRenderer(): callable
    {
        return function (ContentOutputGenerated $event) {
            $content = $event->getContent();
            $metaData = $content->getMetaData();
            $destination = $content->getDestination();
            $id = $content->getID();
            $render = in_array($id, $this->pendingTables);//isset($this->pendingTables[$destination]);
            if (!$render && !$this->collectTOC) {
                return;
            }
            $dom = $event->getDOM();
            if($dom === null) {
                return;
            }
            $xpath = new \DOMXPath($dom);
            $tree = $this->getTableOfContentsTree($xpath->query("//h1|//h2|//h3|//h4|//h5|//h6"), $destination);

            // Use this for the global TOC
            if ($this->collectTOC && !($metaData['skip-from-toc'] ?? false)) {
                $this->globalTOC[$destination] = $tree;
            }
            if ($render) {
                // If there is just a single h1 shave it off and assume it's the title.
                if (count($tree) == 1 && !empty($tree[0]['children']) && (!isset($metaData['title']) || $tree[0]['title'] == $content->getMetaData()['title'])) {
                    $tree = $tree[0]['children'];
                }
                $tocContainer = $xpath->query("//div[@fntoc='{$id}']")->item(0);
                $toc = $dom->createDocumentFragment();
                $toc->appendXML($this->templateEngine->render('table_of_contents_tag', ['tree' => $tree]));
                $tocContainer->appendChild($toc);
                $tocContainer->removeAttribute("fntoc");
            }
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
    private function getTableOfContentsTree(\DOMNodeList $nodes, string $destination, int $level = 1, int $index = 0): array
    {
        $tocTree = [];

        for ($i = $index; $i < $nodes->length; $i++) {
            $node = $nodes->item($i);
            if ($node && $node->nodeName == "h{$level}") {
                $id = $this->makeId($node->textContent) . "-" . ($i + 1);
                $nextItem = $nodes->item($i + 1);
                $nextItemLevel = (int)(isset($nextItem) ? substr($nextItem->nodeName, -1) : 0);
                $node->setAttribute("id", $id);

                if ($nextItemLevel === $level) {
                    // Add node and continue to node on same level
                    $tocTree[] = array(
                        'id' => $id,
                        'title' => $node->nodeValue,
                        'level' => $level,
                        'children' => array(),
                        'destination' => $destination
                    );
                } else if ($nextItemLevel < $level) {
                    // Add node on current level and exit for node on lower level
                    $tocTree[] = array(
                        'id' => $id,
                        'title' => $node->nodeValue,
                        'level' => $level,
                        'children' => array(),
                        'destination' => $destination
                    );
                    break;
                } else {
                    $children = $this->getTableOfContentsTree($nodes, $destination, $nextItemLevel, $i + 1);
                    $newIndex = $children['index'];
                    unset($children['index']);
                    $tocTree[] = array(
                        'id' => $id,
                        'title' => $node->nodeValue,
                        'level' => $level,
                        'children' => $children,
                        'destination' => $destination
                    );
                    $i = $newIndex;
                }
            } else {
                break;
            }
        }

        if ($level > 1) {
            $tocTree['index'] = $i;
        }
        return $tocTree;
    }

    /**
     * Get the global table of contents that was generated across all pages.
     * 
     * @return array
     */
    public function getGlobalTOC(): array
    {
        $toc = [];
        foreach ($this->globalTOC as $contentTOC) {
            foreach ($contentTOC as $item) {
                $toc[] = $item;
            }
        }
        return $toc;
    }
}
