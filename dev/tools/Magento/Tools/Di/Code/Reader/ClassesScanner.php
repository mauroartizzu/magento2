<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Tools\Di\Code\Reader;

use Magento\Framework\Exception\FileSystemException;
use Zend\Code\Scanner\FileScanner;

class ClassesScanner implements ClassesScannerInterface
{
    /**
     * @var array
     */
    protected $excludePatterns = [];

    /**
     * @param array $excludePatterns
     */
    public function __construct(array $excludePatterns = [])
    {
        $this->excludePatterns = $excludePatterns;
    }

    /**
     * Adds exclude patterns
     *
     * @param array $excludePatterns
     * @return void
     */
    public function addExcludePatterns(array $excludePatterns)
    {
        $this->excludePatterns = array_merge($this->excludePatterns, $excludePatterns);
    }

    /**
     * Retrieves list of classes for given path
     *
     * @param string $path
     * @return array
     * @throws FileSystemException
     */
    public function getList($path)
    {
        $realPath = realpath($path);
        if (!(bool)$realPath) {
            throw new FileSystemException(new \Magento\Framework\Phrase('Invalid path: %1', $realPath));
        }

        $recursiveIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($realPath, \FilesystemIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $classes = [];
        foreach ($recursiveIterator as $fileItem) {
            /** @var $fileItem \SplFileInfo */
            if ($fileItem->getExtension() !== 'php') {
                continue;
            }
            foreach ($this->excludePatterns as $excludePattern) {
                if (preg_match($excludePattern, $fileItem->getRealPath())) {
                    continue 2;
                }
            }
            $fileScanner = new FileScanner($fileItem->getRealPath());
            $classNames = $fileScanner->getClassNames();
            foreach ($classNames as $className) {
                if (!class_exists($className)) {
                    require_once $fileItem->getRealPath();
                }
                $classes[] = $className;
            }
        }
        return $classes;
    }
}
