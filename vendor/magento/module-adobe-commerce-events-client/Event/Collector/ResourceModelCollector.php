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
use Magento\Framework\Model\ResourceModel\AbstractResource;
use SplFileInfo;

/**
 * Collects Resource model events from the Adobe Commerce module.
 */
class ResourceModelCollector implements CollectorInterface
{
    /**
     * @var DriverInterface
     */
    private DriverInterface $filesystem;

    /**
     * @var FileOperator
     */
    private FileOperator $fileOperator;

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
     * Collects Resource model events from the Adobe Commerce module.
     *
     * @param string $modulePath
     * @return EventData[]
     */
    public function collect(string $modulePath): array
    {
        $events = [];
        $realPath = $this->filesystem->getRealPath($modulePath . '/Model/ResourceModel');

        try {
            if (!$realPath || !$this->filesystem->isDirectory($realPath)) {
                return $events;
            }
        } catch (FileSystemException $e) {
            return $events;
        }

        $directoryIterator = $this->fileOperator->getRecursiveFileIterator($realPath, ['/\.php$/']);

        foreach ($directoryIterator as $fileItem) {
            /** @var $fileItem SplFileInfo */
            if ($fileItem->isDir()) {
                continue;
            }

            try {
                $className = $this->nameFetcher->getNameFromFile($fileItem);
                $refClass = $this->reflectionClassFactory->create($className);
            } catch (Exception $e) {
                continue;
            }

            if (!$refClass->isSubclassOf(AbstractResource::class)) {
                continue;
            }
            // phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $events = array_merge($events, $this->eventMethodCollector->collect($refClass));
        }

        return $events;
    }
}
