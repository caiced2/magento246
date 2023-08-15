<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableCheckout\Model\ResourceModel\Quote\Item;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class Consumer
{
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Item
     */
    private $itemResource;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item $itemResource
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote\Item $itemResource,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->itemResource = $itemResource;
        $this->cartRepository = $cartRepository;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function processMessage(\Magento\Catalog\Api\Data\ProductInterface $product)
    {
        $connection = $this->itemResource->getConnection();
        $select = $connection->select()->reset();
        $select->from($this->itemResource->getMainTable(), ['quote_id']);
        $select->where('product_id = ?', $product->getId());

        $quoteIds = $connection->fetchCol($select);

        $connection->delete(
            $this->itemResource->getMainTable(),
            'product_id = ' . $product->getId()
        );

        foreach ($quoteIds as $quoteId) {
            try {
                $quote = $this->cartRepository->get($quoteId);
                $this->cartRepository->save($quote);
            } catch (\Exception $exception) {
                $this->logger->critical($exception);
                continue;
            }
        }
    }
}
