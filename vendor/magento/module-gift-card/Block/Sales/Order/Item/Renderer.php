<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Block\Sales\Order\Item;

/**
 * @api
 * @since 100.0.2
 */
class Renderer extends \Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer
{
    /**
     * Prepare custom option for display, returns false if there's no value
     *
     * @param string $code
     * @return string|false
     */
    protected function _prepareCustomOption($code)
    {
        if ($option = $this->getOrderItem()->getProductOptionByCode($code)) {
            return $this->escapeHtml($option);
        }
        return false;
    }

    /**
     * Prepare a string containing name and email
     *
     * @param string $name
     * @param string $email
     * @return string
     */
    protected function _getNameEmailString($name, $email)
    {
        return "{$name} &lt;{$email}&gt;";
    }

    /**
     * Get gift card option list
     *
     * @return array
     */
    protected function _getGiftcardOptions()
    {
        $result = [];
        if ($value = $this->_prepareCustomOption('giftcard_sender_name')) {
            if ($email = $this->_prepareCustomOption('giftcard_sender_email')) {
                $value = $this->_getNameEmailString($value, $email);
            }
            $result[] = ['label' => __('Gift Card Sender'), 'value' => $value];
        }
        if ($value = $this->_prepareCustomOption('giftcard_recipient_name')) {
            if ($email = $this->_prepareCustomOption('giftcard_recipient_email')) {
                $value = $this->_getNameEmailString($value, $email);
            }
            $result[] = ['label' => __('Gift Card Recipient'), 'value' => $value];
        }
        if ($value = $this->_prepareCustomOption('giftcard_message')) {
            $result[] = ['label' => __('Gift Card Message'), 'value' => $value];
        }
        return $result;
    }

    /**
     * Return gift card and custom options array
     *
     * @return array
     */
    public function getItemOptions()
    {
        return array_merge($this->_getGiftcardOptions(), parent::getItemOptions());
    }
}
