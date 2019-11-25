<?php


namespace nyansapow\sites;


class SiteFactory
{
    private $contentFactory;

    public function __construct(ContentFactory $pageFactory)
    {
        $this->contentFactory = $pageFactory;
    }

    public function create(&$metaData, $path, $options): AbstractSite
    {
        if (!is_array($metaData)) {
            $metaData = ['name' => $options['site-name'] ?? '', 'type' => $options['site-type'] ?? 'plain'];
        }
        $class = "\\nyansapow\\sites\\" . ucfirst($metaData['type'] ?? 'plain') . "Site";
        $metaData['excluded_paths'] = ['*/.', '*/..', "*/.*", "*/site.yml", "*/site.yaml", $options['output'], "*/np_*"] + ($metaData['excluded_paths'] ?? []);

        /** @var AbstractSite $site */
        $site = new $class();
        $site->setPath(substr($path, strlen($options['input'])));
        $site->setSourceRoot($options['input']);
        $site->setDestinationRoot($options['output']);
        $site->setSettings($metaData);
        $site->setContentFactory($this->contentFactory);

        return $site;
    }
}
