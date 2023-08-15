<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Gift wrapping info block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\GiftWrapping\Block\Product;

/**
 * @api
 * @since 100.0.2
 */
class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\GiftWrapping\Model\WrappingFactory
     */
    protected $_wrappingFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\GiftWrapping\Model\WrappingFactory $wrappingFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\GiftWrapping\Model\WrappingFactory $wrappingFactory,
        array $data = []
    ) {
        $this->_wrappingFactory = $wrappingFactory;
        parent::__construct($context, $data);
    }

    /**
     * Return product gift wrapping info
     *
     * @return false|\Magento\Framework\DataObject
     */
    public function getGiftWrappingInfo()
    {
        $wrappingId = null;
        if ($this->getLayout()->getBlock('additional.product.info')) {
            $wrappingId = $this->getLayout()->getBlock('additional.product.info')->getItem()->getGwId();
        }

        if ($wrappingId) {
            return $this->_wrappingFactory->create()->load($wrappingId);
        }
        return false;
    }
}
