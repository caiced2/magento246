<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Block\Adminhtml\Catalog\Product\Composite\Fieldset;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Store\Model\Store;
use Magento\GiftCard\Block\Catalog\Product\View\Type\Giftcard as ParentGiftcard;

/**
 * @api
 * @since 100.0.2
 */
class Giftcard extends ParentGiftcard
{
    /**
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param Session $customerSession
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     * @param Product|null $productHelper
     * @param PricingHelper|null $pricingHelper
     */
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        Session $customerSession,
        PriceCurrencyInterface $priceCurrency,
        array $data = [],
        Product $productHelper = null,
        PricingHelper $pricingHelper = null
    ) {
        $data['productHelper'] = $productHelper ?? ObjectManager::getInstance()->get(Product::class);
        $data['pricingHelper'] = $pricingHelper ?? ObjectManager::getInstance()->get(PricingHelper::class);

        parent::__construct($context, $arrayUtils, $customerSession, $priceCurrency, $data);
    }

    /**
     * Checks whether block is last fieldset in popup
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsLastFieldset()
    {
        if ($this->hasData('is_last_fieldset')) {
            return $this->getData('is_last_fieldset');
        } else {
            return !$this->getProduct()->getOptions();
        }
    }

    /**
     * Get current currency code
     *
     * @param null|string|bool|int|Store $storeId
     * @return string
     * @codeCoverageIgnore
     */
    public function getCurrentCurrencyCode($storeId)
    {
        return $this->_storeManager->getStore($storeId)->getCurrentCurrencyCode();
    }
}
