<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Helper\Catalog\Product;

/**
 * Helper for fetching properties by product configurational item
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Configuration extends \Magento\Framework\App\Helper\AbstractHelper implements
    \Magento\Catalog\Helper\Product\Configuration\ConfigurationInterface
{
    /**
     * Catalog product configuration
     *
     * @var \Magento\Catalog\Helper\Product\Configuration|null
     */
    protected $_ctlgProdConfigur = null;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $_escaper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Helper\Product\Configuration $ctlgProdConfigur
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Helper\Product\Configuration $ctlgProdConfigur,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->_ctlgProdConfigur = $ctlgProdConfigur;
        $this->_escaper = $escaper;
        parent::__construct($context);
    }

    /**
     * Prepare custom option for display, returns false if there's no value
     *
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item
     * @param string $code
     * @return string|false
     */
    public function prepareCustomOption(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item, $code)
    {
        $option = $item->getOptionByCode($code);
        if ($option) {
            $value = $option->getValue();
            if ($value) {
                return $this->_escaper->escapeHtml($value);
            }
        }
        return false;
    }

    /**
     * Get gift card option list
     *
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item
     * @return array
     */
    public function getGiftcardOptions(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item)
    {
        $result = [];
        $value = $this->prepareCustomOption($item, 'giftcard_sender_name');
        if ($value) {
            $email = $this->prepareCustomOption($item, 'giftcard_sender_email');
            if ($email) {
                $value = "{$value} &lt;{$email}&gt;";
            }
            $result[] = ['label' => __('Gift Card Sender'), 'value' => $value, 'option_type' => 'html'];
        }

        $value = $this->prepareCustomOption($item, 'giftcard_recipient_name');
        if ($value) {
            $email = $this->prepareCustomOption($item, 'giftcard_recipient_email');
            if ($email) {
                $value = "{$value} &lt;{$email}&gt;";
            }
            $result[] = ['label' => __('Gift Card Recipient'), 'value' => $value, 'option_type' => 'html'];
        }

        $value = $this->prepareCustomOption($item, 'giftcard_message');
        if ($value) {
            $result[] = ['label' => __('Gift Card Message'), 'value' => $value, 'option_type' => 'html'];
        }

        return $result;
    }

    /**
     * Retrieves product options list
     *
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item
     * @return array
     * @codeCoverageIgnore
     */
    public function getOptions(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item)
    {
        return array_merge($this->getGiftcardOptions($item), $this->_ctlgProdConfigur->getCustomOptions($item));
    }
}
