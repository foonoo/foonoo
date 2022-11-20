<?php
namespace foonoo\commands;

use clearice\io\Io;
use foonoo\CommandInterface;
use ntentan\utils\Filesystem;
use ntentan\utils\exceptions\FileAlreadyExistsException;
use foonoo\sites\SiteTypeRegistry;
use Symfony\Component\Yaml\Yaml;

class CreateCommand implements CommandInterface
{
    private $io;
    private $siteTypeRegistry;
    
    public function __construct(Io $io, SiteTypeRegistry $siteTypeRegistry)
    {
        $this->io = $io;
        $this->siteTypeRegistry = $siteTypeRegistry;
    }
    
    public function execute(array $options = [])
    {
        $siteType = $options["__args"][0];
        $factory = $this->siteTypeRegistry->get($siteType);
        $site = $factory->create();
        $siteMetadata = $this->getInitialMetadata($siteType, $options);
        $site->initialize($options['input'] ?? '.', $siteMetadata);
        $this->writeSiteYml($siteMetadata);
    }
    
    
    private function getInitialMetadata(string $siteType, array $options) : array
    {
        return [
            "type" => $siteType,
            "title" => $options['title'] ?? "A foonoo $siteType site",
            "description" => $options['description'] ?? "This is a foonoo $siteType site."
        ];
    }
    
    /**
     * Creates a new site.yml file in the target directory.
     * In case one exists, notify and skip. In cases where it is forced overwrite the existing.
     * 
     * @param array $options
     */
    private function writeSiteYml(array $options) : void
    {
        $inputPath = $options['input'] ?? '.';
        $file = Filesystem::file($inputPath . "/site.yml");
        try {
            Filesystem::checkNotExists($file->getPath());
        } catch (FileAlreadyExistsException $e) {
            if(!isset($options['force'])) {
                $this->io->error("A site probably exists in this location because we found a site.yml file.\n");
                exit(102);
            }
        }
        


        // Any other elegant way? templates?
        $input = "# Default configuration \n\n" . Yaml::dump($options) .
        <<< SITE_YML
        # plugins:
        #   - contrib/highlight
        #   - contrib/responsive_images
        SITE_YML;
        
        $file->putContents($input);
        $this->io->output("Created the site.yml file.\n");
        
        $paths = ['media', 'assets'];
        foreach($paths as $path) {
            $dir = Filesystem::directory("$inputPath/$path");
            $dir->createIfNotExists(true);
        }
        $this->io->output("Created the _foonoo directory.\n");
        
    }
}

