<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableInventory\Model\ResourceModel;

use Magento\CatalogInventory\Model\ResourceModel\Stock;
use Magento\ScalableInventory\Api\Counter\ItemsInterface;
use Psr\Log\LoggerInterface;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class QtyCounterConsumer
{
    /**
     * @var Stock
     */
    private $stockResource;

    /**
     * @var int
     */
    private $numberAttempts;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Stock $stockResource
     * @param LoggerInterface $logger
     * @param int $numberAttempts
     */
    public function __construct(Stock $stockResource, LoggerInterface $logger, $numberAttempts = 5)
    {
        $this->stockResource = $stockResource;
        $this->numberAttempts = $numberAttempts;
        $this->logger = $logger;
    }

    /**
     * @param ItemsInterface $message
     * @return void
     */
    public function processMessage(ItemsInterface $message)
    {
        $items = [];
        foreach ($message->getItems() as $item) {
            $items[$item->getProductId()] = $item->getQty();
        }

        for ($i = 0; $i < $this->numberAttempts; $i++) {
            try {
                $this->stockResource->correctItemsQty($items, $message->getWebsiteId(), $message->getOperator());
                break;
            } catch (\Exception $e) {
                $this->logger->critical(
                    'Consumer can\'t execute message, start attempt number '
                    . ($i + 1) . ', error message: ' . (string)$e
                );
                continue;
            }
        }
    }
}
