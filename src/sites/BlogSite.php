<?php
namespace foonoo\sites;

use foonoo\content\BlogContentFactory;
use foonoo\content\BlogListingContent;
use foonoo\content\BlogPostContent;
use foonoo\utils\Nomenclature;
use clearice\io\Io;

/**
 * Represents a blog site that has posts and pages.
 * @package foonoo\sites
 */
class BlogSite extends AbstractSite
{
    use Nomenclature;

    /**
     * An array of all the posts in the blog site.
     * @var array 
     */
    private $posts = [];

    /**
     * An array of posts organized by dates. This is used for creating the historical archives.
     * @var array 
     */
    private $archives = [];

    /**
     * Content factory for creating blog posts and pages.
     * @var BlogContentFactory 
     */
    private $blogContentFactory;

    /**
     * When set, this property contains an array of frontmatter properties from which taxonomies should be built.
     * @var array|null 
     */
    private $taxonomies;
    

    /**
     * 
     * @var Io
     */
    private $io;

    /**
     * BlogSite constructor.
     *
     * @param BlogContentFactory $blogContentFactory
     */
    public function __construct(BlogContentFactory $blogContentFactory, \clearice\io\Io $io)
    {
        $this->blogContentFactory = $blogContentFactory;
        $this->io = $io;
    }

    /**
     * Return all taxonomies created for the site.
     * 
     * Taxonomies are returned as an associative array where the keys represent 
     * machine names for the taxonomies, and values represent the corresponding 
     * human readable labels.
     *
     * @return array
     */
    public function getTaxonomies()
    {
        if (!$this->taxonomies) {
            $this->taxonomies = [];
            foreach ($this->metaData['taxonomies'] ?? [] as $taxonomy => $taxonomyLabel) {
                if (is_numeric($taxonomy) && !is_numeric($taxonomyLabel)) {
                    $taxonomy = $taxonomyLabel;
                    $taxonomyLabel = $this->makeLabel($taxonomy);
                }
                $this->taxonomies[$taxonomy] = $taxonomyLabel;
            }
        }
        return $this->taxonomies;
    }

    /**
     * Get all the blog pages
     * @return array
     */
    public function getContent(): array
    {
        if(!file_exists($this->getSourcePath("posts"))) {
            return [];
        }
        $content = $this->posts = $this->getBlogPosts($this->getFiles("posts"));
        $content[] = $this->getIndexPage('index.html', $this->posts, "", 'index');
        $content[] = $this->getIndexPage('posts.html', $this->posts);
        $content = array_merge($content, $this->getBlogPages(), $this->getArchive($this->archives, ['months', 'days'], 'years'));
        foreach ($this->getTaxonomies() as $taxonomy => $taxonomyLabel) {
            $content = array_merge($content, $this->getPostsWithTaxonomy($taxonomy, $taxonomyLabel));
        }
        return $content;
    }

    /**
     * Returns a listing page for all the posts that are passed to this function.
     *
     * @param $target
     * @param $posts
     * @param string $title
     * @param string $template
     * @return BlogListingContent
     */
    private function getIndexPage($target, $posts, $title = 'Posts', $template = 'listing'): BlogListingContent
    {
        $data = $this->getTemplateData($this->getDestinationPath($target));
        $data['listing_title'] = $title;
        $data['previews'] = true;
        $listingContent = $this->blogContentFactory->createListing($posts, $target, $data, $title);
        $listingContent->setTemplate($template);
        return $listingContent;
    }

    /**
     * Return a hierarchical list of posts that were made within a particular period.
     *
     * @param array $archive posts to be archived
     * @param array $order The order, in terms of period, in which posts should be categorized.
     * @param null $stage The current stage of the order.
     * @param string $title The title of the archive.
     * @param string $baseUrl The base URL on which to build the archive.
     * @return array
     */
    private function getArchive(array $archive, $order = array(), $stage = null, $title = 'Archive', $baseUrl = ''): array
    {
        $pages = [];
        $nextStage = array_shift($order);
        foreach ($archive as $value => $posts) {
            $newTitle = $this->formatValue($stage, $value) . " $title";
            $newBaseUrl = "$baseUrl$value/";
            $pages[] = $this->getIndexPage("{$newBaseUrl}index.html", $posts['posts'], $newTitle);
            if ($nextStage != null) {
                $pages = array_merge($pages, $this->getArchive($posts[$nextStage], $order, $nextStage, $newTitle, $newBaseUrl));
            }
        }

        return $pages;
    }

    private function formatValue($stage, $value)
    {
        switch ($stage) {
            case 'days':
            case 'years':
                return $value;
            case 'months':
                return date("M", $value);
        }
    }

    private function getBlogPosts($files): array
    {
        $pages = [];
        /** @var BlogPostContent $nextPost */
        $nextPost = null;
        rsort($files);

        foreach ($files as $file) {
            if (preg_match("/(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})-(?<title>[A-Za-z0-9\-\_]*)\.(md)/", $file, $matches)) {
                $destinationPath = "{$matches['year']}/{$matches['month']}/{$matches['day']}/{$matches['title']}.html";
                // Force content factory to generate blog content
                $page = $this->blogContentFactory->createPost($this->getSourcePath($file), $destinationPath);
                $page->setTemplateData($this->getTemplateData($this->getDestinationPath($page->getDestination())));
                $page->setSiteTaxonomies($this->getTaxonomies());
                $pages[] = $page;
                if ($nextPost) {
                    $page->setNext($nextPost);
                    $nextPost->setPrevious($page);
                }
                $nextPost = $page;
                $this->addPostToArchive($page, $matches);
            } else {
                $this->io->output("* Skipping [{$file}] for consideration in blog posts. Files used as blog posts must follow the format YYYY-MM-DD-Title.ext.\n");
            }
        }

        return $pages;
    }

    private function getBlogPages(): array
    {
        $pages = [];
        if (!file_exists($this->getSourcePath('pages'))) {
            return $pages;
        }
        $files = $this->getFiles('pages');
        foreach ($files as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            $destinationPath = "$filename.html";
            $page = $this->blogContentFactory->createPage($this->getSourcePath($file), $destinationPath);
            $pages[] = $page;
        }
        return $pages;
    }

    private function addPostToArchive($page, $matches)
    {
        if (!isset($this->archives[$matches['year']])) {
            $this->archives[$matches['year']] = ['posts' => []];
        }
        if (!isset($this->archives[$matches['year']][$matches['month']])) {
            $this->archives[$matches['year']][$matches['month']] = ['posts' => []];
        }
        if (!isset($this->archives[$matches['year']][$matches['month']][$matches['day']])) {
            $this->archives[$matches['year']][$matches['month']][$matches['day']] = ['posts' => []];
        }
        $this->archives[$matches['year']]['posts'][] = $page;
        $this->archives[$matches['year']]['months'][$matches['month']]['posts'][] = $page;
        $this->archives[$matches['year']]['months'][$matches['month']]['days'][$matches['day']]['posts'][] = $page;
    }

    private function getPostsWithTaxonomy($taxonomy, $taxonomyLabel)
    {
        $selected = [];
        $pages = [];
        foreach ($this->posts as $post) {
            if (isset($post->getMetaData()['frontmatter'][$taxonomy])) {
                $taxonomyValues = $post->getMetaData()['frontmatter'][$taxonomy];
                foreach (is_array($taxonomyValues) ? $taxonomyValues : [$taxonomyValues] as $value) {
                    if (isset($selected[$value])) {
                        $selected[$value][] = $post;
                    } else {
                        $selected[$value] = [$post];
                    }
                }
            }
        }

        $taxonomyIds = [];
        foreach ($selected as $label => $posts) {
            $taxonomyId = $this->makeId($label, $taxonomyIds);
            $taxonomyIds[] = $taxonomyId;
            $pages[] = $this->getIndexPage("$taxonomy/$taxonomyId.html", $posts, $label);
        }

        return $pages;
    }

    public function getType(): string
    {
        return 'blog';
    }

    public function getDefaultTheme(): string
    {
        return 'blog';
    }
    
    public function getTemplateData(mixed $contentDestination = null): array
    {
        $templateData = parent::getTemplateData($contentDestination);
        $templateData['site_tagline'] = $this->metaData['tagline'] ?? '';
        return $templateData;
    }
}
