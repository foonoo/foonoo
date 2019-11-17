<?php


namespace nyansapow\sites;


class SiteFactory
{
    private $pageFactory;

    public function __construct(PageFactory $pageFactory)
    {
        $this->pageFactory = $pageFactory;
    }

    public function create(&$metaData, $path, $options): AbstractSite
    {
        if (!is_array($metaData)) {
            $metaData = ['name' => $options['site-name'] ?? '', 'type' => $options['site-type'] ?? 'plain'];
        }
        $class = "\\nyansapow\\sites\\" . ucfirst($metaData['type'] ?? 'plain') . "Site";
        $metaData['excluded_paths'] = ['*/.', '*/..', "*/.*", "*/site.yml", "*/site.yaml", $options['output'], "*/np_*"] + ($metaData['excluded_paths'] ?? []);

        /** @var AbstractSite $instance */
        $site = new $class();

        $site->setSourcePath($path);
        $site->setDestinationPath($path);
        $site->setSourceRoot($options['input']);
        $site->setDestinationRoot($options['output']);
        $site->setSettings($metaData);
        $site->setPageFactory($this->pageFactory);

        return $site;
    }
}
