<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Item\ToOrderItem as QuoteToOrderItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\GiftCard\Model\Giftcard;
use Magento\Store\Model\ScopeInterface;

/**
 * Plugin for Magento\Quote\Model\Quote\Item\ToOrderItem
 */
class QuoteItem
{
    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Perform conversion for gift card
     *
     * @param QuoteToOrderItem $subject
     * @param OrderItem $orderItem
     * @param AbstractItem $quoteItem
     * @param array $data
     * @return OrderItem
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterConvert(QuoteToOrderItem $subject, OrderItem $orderItem, AbstractItem $quoteItem, $data = [])
    {
        $keys = [
            'giftcard_sender_name',
            'giftcard_sender_email',
            'giftcard_recipient_name',
            'giftcard_recipient_email',
            'giftcard_message',
        ];
        $productOptions = $orderItem->getProductOptions();
        $product = $quoteItem->getProduct();

        foreach ($keys as $key) {
            $option = $product->getCustomOption($key);

            if ($option) {
                $productOptions[$key] = $option->getValue();
            }
        }

        // set lifetime
        if ($product->getUseConfigLifetime()) {
            $lifetime = $this->scopeConfig->getValue(
                Giftcard::XML_PATH_LIFETIME,
                ScopeInterface::SCOPE_STORE,
                $orderItem->getStore()
            );
        } else {
            $lifetime = $product->getLifetime();
        }

        $productOptions['giftcard_lifetime'] = $lifetime;

        // set is_redeemable
        if ($product->getUseConfigIsRedeemable()) {
            $isRedeemable = $this->scopeConfig->isSetFlag(
                Giftcard::XML_PATH_IS_REDEEMABLE,
                ScopeInterface::SCOPE_STORE,
                $orderItem->getStore()
            );
        } else {
            $isRedeemable = (int)$product->getIsRedeemable();
        }

        $productOptions['giftcard_is_redeemable'] = $isRedeemable;

        // set email_template
        if ($product->getUseConfigEmailTemplate()) {
            $emailTemplate = $this->scopeConfig->getValue(
                Giftcard::XML_PATH_EMAIL_TEMPLATE,
                ScopeInterface::SCOPE_STORE,
                $orderItem->getStore()
            );
        } else {
            $emailTemplate = $product->getEmailTemplate();
        }

        $productOptions['giftcard_email_template'] = $emailTemplate;
        $productOptions['giftcard_type'] = $product->getGiftcardType();
        $orderItem->setProductOptions($productOptions);

        return $orderItem;
    }
}
