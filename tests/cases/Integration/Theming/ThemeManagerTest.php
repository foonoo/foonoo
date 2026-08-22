<?php

namespace foonoo\tests\Integration\Theming;

use foonoo\sites\DefaultSiteFactory;
use foonoo\tests\BaseTestCase;
use foonoo\theming\Theme;
use foonoo\theming\ThemeManager;
use Symfony\Component\Yaml\Parser;

class ThemeManagerTest extends BaseTestCase
{
    public function testLoadCustomThemeFromHierarchy(): void
    {
        $themeDir = $this->createVfsDirectory('themes/custom_theme');
        $this->createVfsFile(
            'themes/custom_theme/theme.yaml',
            "name: custom_theme\ndescription: A custom theme\ndefault_layout: layout\nassets:\n  css:\n    - custom.css\n"
        );
        $this->createVfsFile('themes/custom_theme/templates/layout.tpl.php', '<html><body><?= $body ?></body></html>');
        $this->createVfsFile('themes/custom_theme/assets/custom.css', 'body { color: blue; }');

        $container = $this->getTestContainer();
        /** @var ThemeManager $themeManager */
        $themeManager = $container->get(ThemeManager::class);
        $themeManager->reset();
        $themeManager->prependToThemePath($this->getVfsUrl('themes'));

        /** @var DefaultSiteFactory $siteFactory */
        $siteFactory = $container->get(DefaultSiteFactory::class);
        $site = $siteFactory->create();
        $site->setPath('');
        $site->setSourceRoot($this->createVfsDirectory('site_src') . '/');
        $site->setDestinationRoot($this->createVfsDirectory('site_dst') . '/');
        $site->setMetaData(['theme' => 'custom_theme', 'type' => 'default']);
        $site->setAssetPipeline($container->get(\foonoo\asset_pipeline\AssetPipelineFactory::class)->create());

        $theme = $themeManager->getTheme($site);
        $this->assertInstanceOf(Theme::class, $theme);
        $this->assertSame('layout', $theme->getDefaultLayoutTemplate());
        $this->assertSame($this->getVfsUrl('themes/custom_theme'), $theme->getPath());
    }
}
