<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Block\Catalog\Product\View\Type;

use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * @api
 * @since 100.0.2
 */
class Giftcard extends \Magento\Catalog\Block\Product\View\AbstractView
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Stdlib\ArrayUtils $arrayUtils
     * @param \Magento\Customer\Model\Session $customerSession
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Customer\Model\Session $customerSession,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->_customerSession = $customerSession;
        parent::__construct(
            $context,
            $arrayUtils,
            $data
        );
    }

    /**
     * Gets an amount settings.
     *
     * @param Product $product
     * @return array
     */
    public function getAmountSettingsJson($product)
    {
        $result = ['min' => 0, 'max' => 0];
        if ($product->getAllowOpenAmount()) {
            if ($v = $product->getOpenAmountMin()) {
                $result['min'] = $v;
            }
            if ($v = $product->getOpenAmountMax()) {
                $result['max'] = $v;
            }
        }
        return $result;
    }

    /**
     * Checks if gift card is configured.
     *
     * @param Product $product
     * @return bool
     */
    public function isConfigured($product)
    {
        if (!$product->getAllowOpenAmount() && !$product->getGiftcardAmounts()) {
            return false;
        }
        return true;
    }

    /**
     * Checks if open amount available for gift card.
     *
     * @param Product $product
     * @return bool
     */
    public function isOpenAmountAvailable($product)
    {
        if (!$product->getAllowOpenAmount()) {
            return false;
        }
        return true;
    }

    /**
     * Checks if gift card amount is available.
     *
     * @param Product $product
     * @return bool
     */
    public function isAmountAvailable($product)
    {
        if (!$product->getGiftcardAmounts()) {
            return false;
        }
        return true;
    }

    /**
     * Gets sorted gift card amounts.
     *
     * @param Product $product
     * @return array
     */
    public function getAmounts($product)
    {
        $result = [];
        foreach ($product->getGiftcardAmounts() as $amount) {
            $result[] = $this->priceCurrency->round($amount['website_value']);
        }
        sort($result);
        return $result;
    }

    /**
     * Gets currency code of current store.
     *
     * @return string
     */
    public function getCurrentCurrency()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Checks if gift card message is available.
     *
     * @param Product $product
     * @return bool|int
     */
    public function isMessageAvailable($product)
    {
        if ($product->getUseConfigAllowMessage()) {
            return $this->_scopeConfig->isSetFlag(
                \Magento\GiftCard\Model\Giftcard::XML_PATH_ALLOW_MESSAGE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        } else {
            return (int)$product->getGiftMessageAvailable();
        }
    }

    /**
     * Checks if email is available for gift card.
     *
     * @param Product $product
     * @return bool
     */
    public function isEmailAvailable($product)
    {
        if ($product->getTypeInstance()->isTypePhysical($product)) {
            return false;
        }
        return true;
    }

    /**
     * Gets concated customer name.
     *
     * @return string
     */
    public function getCustomerName()
    {
        $firstName = (string)$this->_customerSession->getCustomer()->getFirstname();
        $lastName = (string)$this->_customerSession->getCustomer()->getLastname();

        if ($firstName && $lastName) {
            return $firstName . ' ' . $lastName;
        } else {
            return '';
        }
    }

    /**
     * Gets customer email.
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        return (string)$this->_customerSession->getCustomer()->getEmail();
    }

    /**
     * Gets max length of gift card message.
     *
     * @return int
     */
    public function getMessageMaxLength()
    {
        return (int)$this->_scopeConfig->getValue(
            \Magento\GiftCard\Model\Giftcard::XML_PATH_MESSAGE_MAX_LENGTH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Returns default value to show in input
     *
     * @param string $key
     * @return string
     */
    public function getDefaultValue($key)
    {
        return (string)$this->getProduct()->getPreconfiguredValues()->getData($key);
    }

    /**
     * Returns default sender name to show in input
     *
     * @return string
     */
    public function getDefaultSenderName()
    {
        $senderName = $this->getProduct()->getPreconfiguredValues()->getData('giftcard_sender_name');

        if (!$senderName) {
            $senderName = $this->getCustomerName();
        }

        return $senderName;
    }

    /**
     * Returns default sender email to show in input
     *
     * @return string
     */
    public function getDefaultSenderEmail()
    {
        $senderEmail = $this->getProduct()->getPreconfiguredValues()->getData('giftcard_sender_email');

        if (!$senderEmail) {
            $senderEmail = $this->getCustomerEmail();
        }

        return $senderEmail;
    }
}
