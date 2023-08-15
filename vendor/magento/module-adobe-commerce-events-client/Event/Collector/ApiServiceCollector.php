<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Collector;

use Exception;
use Magento\AdobeCommerceEventsClient\Util\FileOperator;
use Magento\Framework\App\Utility\ReflectionClassFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DriverInterface;
use SplFileInfo;

/**
 * Collects API interfaces events from the Adobe Commerce module.
 */
class ApiServiceCollector implements CollectorInterface
{
    /**
     * @var DriverInterface
     */
    private DriverInterface $filesystem;

    /**
     * @var NameFetcher
     */
    private NameFetcher $nameFetcher;

    /**
     * @var EventMethodCollector
     */
    private EventMethodCollector $eventMethodCollector;

    /**
     * @var ReflectionClassFactory
     */
    private ReflectionClassFactory $reflectionClassFactory;

    /**
     * @var FileOperator
     */
    private FileOperator $fileOperator;

    /**
     * @param DriverInterface $filesystem
     * @param FileOperator $fileOperator
     * @param NameFetcher $nameFetcher
     * @param EventMethodCollector $eventMethodCollector
     * @param ReflectionClassFactory $reflectionClassFactory
     */
    public function __construct(
        DriverInterface $filesystem,
        FileOperator $fileOperator,
        NameFetcher $nameFetcher,
        EventMethodCollector $eventMethodCollector,
        ReflectionClassFactory $reflectionClassFactory
    ) {
        $this->filesystem = $filesystem;
        $this->fileOperator = $fileOperator;
        $this->nameFetcher = $nameFetcher;
        $this->eventMethodCollector = $eventMethodCollector;
        $this->reflectionClassFactory = $reflectionClassFactory;
    }

    /**
     * Collects API interfaces events from the Adobe Commerce module.
     *
     * @param string $modulePath
     * @return EventData[]
     */
    public function collect(string $modulePath): array
    {
        $events = [];
        $realPath = $this->filesystem->getRealPath($modulePath . '/Api');

        try {
            if (!$realPath || !$this->filesystem->isDirectory($realPath)) {
                return $events;
            }
        } catch (FileSystemException $e) {
            return $events;
        }

        $directoryIterator = $this->fileOperator->getDirectoryIterator($realPath);

        foreach ($directoryIterator as $fileItem) {
            /** @var $fileItem SplFileInfo */
            if ($fileItem->isDir() || $fileItem->getExtension() !== 'php') {
                continue;
            }

            try {
                $interface = $this->nameFetcher->getNameFromFile($fileItem);
                $refClass = $this->reflectionClassFactory->create($interface);
            } catch (Exception $e) {
                continue;
            }

            // phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $events = array_merge($events, $this->eventMethodCollector->collect($refClass));
        }

        return $events;
    }
}
