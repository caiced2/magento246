<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Email;

use Magento\GiftRegistry\Model\ResourceModel\Item\CollectionFactory as CollectionFactory;
use Magento\Framework\View\Element\Template\Context;

/**
 * Update email template gift registry items block
 *
 * @api
 * @since 100.0.2
 */
class Items extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\GiftRegistry\Model\ResourceModel\Item\CollectionFactory;
     */
    private $entityCollectionFactory;

    /**
     * @param Context $context
     * @param array $data
     * @param CollectionFactory|null $collectionFactory
     */
    public function __construct(
        Context $context,
        array $data = [],
        CollectionFactory $collectionFactory = null
    ) {
        $this->entityCollectionFactory = $collectionFactory ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(CollectionFactory::class);
        parent::__construct($context, $data);
    }

    /**
     * Return list of gift registry items
     *
     * @return \Magento\GiftRegistry\Model\ResourceModel\Item\Collection
     */
    public function getItems()
    {
        $entity = $this->getEntity();
        if ($entity) {
            return $entity->getItemsCollection();
        }
        return $this->entityCollectionFactory->create();
    }

    /**
     * Count gift registry items in last order
     *
     * @param \Magento\GiftRegistry\Model\ResourceModel\Item $item
     * @return int
     */
    public function getQtyOrdered($item)
    {
        $updatedQty = $this->getEntity()->getUpdatedQty();
        if (is_array($updatedQty) && !empty($updatedQty[$item->getId()]['ordered'])) {
            return $updatedQty[$item->getId()]['ordered'] * 1;
        }
        return 0;
    }

    /**
     * Return gift registry entity remained item qty
     *
     * @param \Magento\GiftRegistry\Model\ResourceModel\Item $item
     * @return int
     */
    public function getRemainedQty($item)
    {
        $qty = ($item->getQty() - $this->getQtyFulfilled($item)) * 1;
        if ($qty > 0) {
            return $qty;
        }
        return 0;
    }

    /**
     * Return gift registry entity item qty
     *
     * @param \Magento\GiftRegistry\Model\ResourceModel\Item $item
     * @return int
     * @codeCoverageIgnore
     */
    public function getQty($item)
    {
        return $item->getQty() * 1;
    }

    /**
     * Return gift registry entity item fulfilled qty
     *
     * @param \Magento\GiftRegistry\Model\ResourceModel\Item $item
     * @return int
     */
    public function getQtyFulfilled($item)
    {
        $updatedQty = $this->getEntity()->getUpdatedQty();
        if (is_array($updatedQty) && !empty($updatedQty[$item->getId()]['fulfilled'])) {
            return $updatedQty[$item->getId()]['fulfilled'] * 1;
        }
        return $item->getQtyFulfilled() * 1;
    }
}
