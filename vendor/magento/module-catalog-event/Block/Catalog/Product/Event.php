<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog Event on category page
 */
namespace Magento\CatalogEvent\Block\Catalog\Product;

use Magento\Catalog\Model\Product;
use \Magento\Framework\DataObject\IdentityInterface;
use \Magento\CatalogEvent\Block\Event\AbstractEvent;

/**
 * @api
 * @since 100.0.2
 */
class Event extends AbstractEvent implements IdentityInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Catalog event data
     *
     * @var \Magento\CatalogEvent\Helper\Data
     */
    protected $_catalogEventData;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\CatalogEvent\Model\DateResolver $dateResolver
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\CatalogEvent\Helper\Data $catalogEventData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\CatalogEvent\Model\DateResolver $dateResolver,
        \Magento\Framework\Registry $registry,
        \Magento\CatalogEvent\Helper\Data $catalogEventData,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_catalogEventData = $catalogEventData;
        parent::__construct($context, $dateResolver, $data);
    }

    /**
     * Return current category event
     *
     * @return \Magento\CatalogEvent\Model\Event
     */
    public function getEvent()
    {
        if ($this->getProduct()) {
            return $this->getProduct()->getEvent();
        }

        return false;
    }

    /**
     * Return current product
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->_coreRegistry->registry('current_product');
    }

    /**
     * Check availability to display event block
     *
     * @return boolean
     */
    public function canDisplay()
    {
        return $this->_catalogEventData->isEnabled()
            && $this->getProduct()
            && $this->getEvent()
            && $this->getEvent()->canDisplayProductPage()
            && !$this->getProduct()->getEventNoTicker();
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        $event = $this->getEvent();
        if ($event) {
            return $event->getIdentities();
        } else {
            $categoryId = $this->getProduct()->getCategoryId();
            return $categoryId ? [Product::CACHE_PRODUCT_CATEGORY_TAG . '_' . $categoryId] : [];
        }
    }
}
