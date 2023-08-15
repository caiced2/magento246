<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Block\Customer;

/**
 * Customer gift registry view items block
 *
 * @api
 * @since 100.0.2
 */
class Items extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * Gift registry item factory
     *
     * @var \Magento\GiftRegistry\Model\ItemFactory
     */
    protected $itemFactory = null;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\GiftRegistry\Model\ItemFactory $itemFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\GiftRegistry\Model\ItemFactory $itemFactory,
        array $data = []
    ) {
        $this->pricingHelper = $pricingHelper;
        $this->itemFactory = $itemFactory;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Return gift registry form header
     *
     * @return \Magento\Framework\Phrase
     */
    public function getFormHeader()
    {
        return __('View Gift Registry %1', $this->getEntity()->getTitle());
    }

    /**
     * Return list of gift registries
     *
     * @return \Magento\GiftRegistry\Model\ResourceModel\Item\Collection
     */
    public function getItemCollection()
    {
        if (!$this->hasItemCollection()) {
            $collection = $this->itemFactory->create()->getCollection()->addRegistryFilter(
                $this->getEntity()->getId()
            );
            $this->setData('item_collection', $collection);
        }
        return $this->_getData('item_collection');
    }

    /**
     * Retrieve item formatted date
     *
     * @param \Magento\GiftRegistry\Model\Item $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getFormattedDate($item)
    {
        return $this->formatDate($item->getAddedAt(), \IntlDateFormatter::MEDIUM);
    }

    /**
     * Retrieve escaped item note
     *
     * @param \Magento\GiftRegistry\Model\Item $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getEscapedNote($item)
    {
        return $this->escapeHtml($item->getData('note'));
    }

    /**
     * Retrieve item qty
     *
     * @param \Magento\GiftRegistry\Model\Item $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getItemQty($item)
    {
        return $item->getQty() * 1;
    }

    /**
     * Retrieve item fulfilled qty
     *
     * @param \Magento\GiftRegistry\Model\Item $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getItemQtyFulfilled($item)
    {
        return $item->getQtyFulfilled() * 1;
    }

    /**
     * Return action form url
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getActionUrl()
    {
        return $this->getUrl('*/*/updateItems', ['_current' => true]);
    }

    /**
     * Return back url
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getBackUrl()
    {
        return $this->getUrl('magento_giftregistry');
    }

    /**
     * Return back url to search result page
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getSearchBackUrl()
    {
        return $this->getUrl('*/search/results');
    }

    /**
     * Returns product price
     *
     * @param \Magento\GiftRegistry\Model\Item $item
     * @return float|string
     * @codeCoverageIgnore
     */
    public function getPrice($item)
    {
        $product = $item->getProduct();
        $product->setCustomOptions($item->getOptionsByCode());
        return $this->pricingHelper->currency($product->getFinalPrice(), true, true);
    }
}
