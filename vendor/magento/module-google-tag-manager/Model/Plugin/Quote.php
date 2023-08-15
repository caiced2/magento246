<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Model\Plugin;

use Magento\Checkout\Model\Session;
use Magento\Framework\Registry;
use Magento\GoogleTagManager\Helper\Data;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Quote
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Data $helper
     * @param Session $checkoutSession
     * @param Registry $registry
     */
    public function __construct(
        Data $helper,
        Session $checkoutSession,
        Registry $registry
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->registry = $registry;
    }

    /**
     * After load pluging
     *
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Magento\Quote\Model\Quote $result
     * @return \Magento\Quote\Model\Quote
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function afterLoad(\Magento\Quote\Model\Quote $subject, $result)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $result;
        }

        $productQtys = [];
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($subject->getAllItems() as $quoteItem) {
            $parentQty = 1;
            switch ($quoteItem->getProductType()) {
                case 'bundle':
                case 'configurable':
                    break;
                case 'grouped':
                    $id = $quoteItem->getOptionByCode('product_type')->getProductId()
                        . '-' . $quoteItem->getProductId();
                    $productQtys[$id] = $quoteItem->getQty();
                    break;
                case 'giftcard':
                    $id = $quoteItem->getId() . '-' . $quoteItem->getProductId();
                    $productQtys[$id] = $quoteItem->getQty();
                    break;
                default:
                    if ($quoteItem->getParentItem()) {
                        $parentQty = $quoteItem->getParentItem()->getQty();
                        $id = $quoteItem->getId() . '-' .
                            $quoteItem->getParentItem()->getProductId() . '-' .
                            $quoteItem->getProductId();
                    } else {
                        $id = $quoteItem->getProductId();
                    }
                    $productQtys[$id] = $quoteItem->getQty() * $parentQty;
            }
        }
        /** prevent from overwriting on page load */
        if (!$this->checkoutSession->hasData(
            TagManagerConfig::PRODUCT_QUANTITIES_BEFORE_ADDTOCART
        )) {
            $this->checkoutSession->setData(
                TagManagerConfig::PRODUCT_QUANTITIES_BEFORE_ADDTOCART,
                $productQtys
            );
        }
        return $result;
    }
}
