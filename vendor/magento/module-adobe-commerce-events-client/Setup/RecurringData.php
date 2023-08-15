<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Setup;

use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\AdobeIoEventMetadataSynchronizer;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\SynchronizerException;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;

/**
 * Register events metadata in Adobe I/O during setup:upgrade.
 */
class RecurringData implements InstallDataInterface
{
    /**
     * @var AdobeIoEventMetadataSynchronizer
     */
    private AdobeIoEventMetadataSynchronizer $eventMetadataSynchronizer;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param AdobeIoEventMetadataSynchronizer $eventMetadataSynchronizer
     * @param LoggerInterface $logger
     */
    public function __construct(
        AdobeIoEventMetadataSynchronizer $eventMetadataSynchronizer,
        LoggerInterface $logger
    ) {
        $this->eventMetadataSynchronizer = $eventMetadataSynchronizer;
        $this->logger = $logger;
    }

    /**
     * Register events metadata in Adobe I/O.
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws EventInitializationException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        try {
            foreach ($this->eventMetadataSynchronizer->run() as $message) {
                $this->logger->info($message);
            }
        } catch (SynchronizerException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
