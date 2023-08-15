<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Collector;

use Magento\AdobeCommerceEventsClient\Event\Collector\ObserverEventsCollector\DispatchMethodCollector;
use Magento\AdobeCommerceEventsClient\Event\Collector\ObserverEventsCollector\EventPrefixesCollector;
use Magento\AdobeCommerceEventsClient\Util\FileOperator;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DriverInterface;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Exception;

/**
 * Collects observer events list
 */
class ObserverEventsCollector implements CollectorInterface
{
    /**
     * @var FileOperator
     */
    private FileOperator $fileOperator;

    /**
     * @var DriverInterface
     */
    private DriverInterface $filesystem;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var DispatchMethodCollector
     */
    private DispatchMethodCollector $dispatchMethodCollector;

    /**
     * @var EventPrefixesCollector
     */
    private EventPrefixesCollector $eventPrefixesCollector;

    /**
     * @var string
     */
    private string $excludeDirPattern;

    /**
     * @param FileOperator $fileOperator
     * @param DriverInterface $filesystem
     * @param LoggerInterface $logger
     * @param DispatchMethodCollector $dispatchMethodCollector
     * @param EventPrefixesCollector $eventPrefixesCollector
     * @param string $excludeDirPattern
     */
    public function __construct(
        FileOperator $fileOperator,
        DriverInterface $filesystem,
        LoggerInterface $logger,
        DispatchMethodCollector $dispatchMethodCollector,
        EventPrefixesCollector $eventPrefixesCollector,
        string $excludeDirPattern = '/^((?!test|Test|dev).)*$/'
    ) {
        $this->fileOperator = $fileOperator;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->dispatchMethodCollector = $dispatchMethodCollector;
        $this->eventPrefixesCollector = $eventPrefixesCollector;
        $this->excludeDirPattern = $excludeDirPattern;
    }

    /**
     * @inheritDoc
     */
    public function collect(string $modulePath): array
    {
        $result = [];

        $regexIterator = $this->fileOperator->getRecursiveFileIterator(
            $modulePath,
            ['/\.php$/', $this->excludeDirPattern]
        );

        foreach ($regexIterator as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            try {
                $fileContent = $this->filesystem->fileGetContents($fileInfo->getPathname());
                if (strpos($fileContent, '$_eventPrefix') !== false) {
                    // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                    $result = array_merge(
                        $result,
                        $this->eventPrefixesCollector->fetchEvents($fileInfo, $fileContent)
                    );
                } elseif (strpos($fileContent, '->dispatch(') !== false) {
                    // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                    $result = array_merge(
                        $result,
                        $this->dispatchMethodCollector->fetchEvents($fileInfo, $fileContent)
                    );
                }
            } catch (FileSystemException $e) {
                $this->logger->error(sprintf(
                    'Unable to get file content during observer events collecting. File %s. Error: %s',
                    $fileInfo->getPathname(),
                    $e->getMessage()
                ));
                continue;
            } catch (Exception $e) {
                $this->logger->error(sprintf(
                    'Unable to collect observer events from the file %s. Error: %s',
                    $fileInfo->getPathname(),
                    $e->getMessage()
                ));
                continue;
            }
        }

        return $result;
    }
}
