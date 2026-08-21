<?php

namespace foonoo\tests;

use ntentan\panie\Container;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected ?vfsStreamDirectory $vfsRoot = null;

    /**
     * Set up a virtual filesystem root with optional initial directory structure.
     *
     * @param string $rootDir Root directory name
     * @param int|null $permissions Optional directory permissions
     * @param array<string, mixed> $structure Initial virtual filesystem structure
     * @return vfsStreamDirectory
     */
    protected function setupVfs(string $rootDir = 'root', ?int $permissions = null, array $structure = []): vfsStreamDirectory
    {
        $this->vfsRoot = vfsStream::setup($rootDir, $permissions, $structure);
        return $this->vfsRoot;
    }

    /**
     * Get the vfs:// URL for a path relative to the virtual filesystem root.
     *
     * @param string $relativePath
     * @return string Full vfs:// path URL
     */
    protected function getVfsUrl(string $relativePath = ''): string
    {
        if ($this->vfsRoot === null) {
            $this->setupVfs();
        }

        $rootName = $this->vfsRoot->getName();
        $trimmed = ltrim($relativePath, '/\\');
        return vfsStream::url($trimmed === '' ? $rootName : $rootName . '/' . $trimmed);
    }

    /**
     * Create a file with content inside the virtual filesystem.
     *
     * @param string $relativePath Path relative to vfs root
     * @param string $content File content
     * @return string Full vfs:// URL to the created file
     */
    protected function createVfsFile(string $relativePath, string $content): string
    {
        $url = $this->getVfsUrl($relativePath);
        $dir = dirname($url);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($url, $content);
        return $url;
    }

    /**
     * Create a directory inside the virtual filesystem.
     *
     * @param string $relativePath Path relative to vfs root
     * @return string Full vfs:// URL to the created directory
     */
    protected function createVfsDirectory(string $relativePath): string
    {
        $url = $this->getVfsUrl($relativePath);
        if (!is_dir($url)) {
            mkdir($url, 0777, true);
        }
        return $url;
    }

    /**
     * Get a pre-configured test container.
     *
     * @param array<string, mixed> $customBindings
     * @return Container
     */
    protected function getTestContainer(array $customBindings = []): Container
    {
        return TestContainerFactory::create($customBindings);
    }
}
