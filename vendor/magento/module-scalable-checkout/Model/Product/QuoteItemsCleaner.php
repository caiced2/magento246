<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableCheckout\Model\Product;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class QuoteItemsCleaner implements \Magento\Quote\Model\Product\QuoteItemsCleanerInterface
{
    const TOPIC_NAME = 'catalog.product.removed';

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    private $publisher;

    /**
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(\Magento\Framework\MessageQueue\PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Catalog\Api\Data\ProductInterface $product)
    {
        $this->publisher->publish(self::TOPIC_NAME, $product);
    }
}
