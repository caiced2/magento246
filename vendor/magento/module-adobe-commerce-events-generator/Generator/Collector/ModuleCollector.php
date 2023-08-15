<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Generator\Collector;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use ReflectionClass;
use Throwable;

/**
 * Collects and store module for the provided classes.
 */
class ModuleCollector
{
    private const MAX_FOLDER_DEPTH = 10;

    /**
     * @var File
     */
    private File $file;

    /**
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * @var array
     */
    private array $modules = [];

    /**
     * Collects and store module for the provided class.
     *
     * @param ReflectionClass $class
     * @return void
     */
    public function collect(ReflectionClass $class): void
    {
        $dir = $this->file->getParentDirectory($class->getFileName());
        $i = 0;

        do {
            try {
                $composerPath = $dir . '/composer.json';
                if ($this->file->isExists($composerPath)) {
                    $composerContent = json_decode($this->file->fileGetContents($composerPath), true);

                    if (isset($composerContent['type']) && $composerContent['type'] === 'magento2-module') {
                        $module = [
                            'packageName' => $composerContent['name'] ?: '',
                        ];

                        $moduleXmlPath = $dir . '/etc/module.xml';
                        if ($this->file->isExists($moduleXmlPath)) {
                            $module['name'] = $this->getModuleName($moduleXmlPath);
                        }

                        $this->modules[$module['packageName']] = $module;

                        return;
                    }
                }
            } catch (FileSystemException $e) {
                continue;
            }
            $i++;
            $dir = $this->file->getParentDirectory($dir);
        } while ($dir != '/' && $i < self::MAX_FOLDER_DEPTH);
    }

    /**
     * Returns list of collected modules.
     *
     * @return array
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Parses module name from `etc/module.xml` file.
     *
     * In case of error returns an empty string.
     *
     * @param string $moduleXmlPath
     * @return string
     */
    private function getModuleName(string $moduleXmlPath): string
    {
        try {
            $moduleXmlContent = simplexml_load_string($this->file->fileGetContents($moduleXmlPath));

            return (string)$moduleXmlContent->module->attributes()->name ?: '';
        } catch (Throwable $e) {
            return '';
        }
    }
}
