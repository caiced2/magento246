<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\Grid;

/**
 * Block with description of why item has not been added to ordered items list
 *
 * @method \Magento\Framework\DataObject                                                   getItem()
 * @method \Magento\Catalog\Model\Product                                      getProduct()
 * @method \Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\Grid\Description setItem()
 * @method \Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\Grid\Description setProduct()
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Description extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'sku/errors/grid/description.phtml';

    /**
     * Checkout data
     *
     * @var \Magento\AdvancedCheckout\Helper\Data
     */
    protected $_checkoutData = null;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\AdvancedCheckout\Helper\Data $checkoutData
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\AdvancedCheckout\Helper\Data $checkoutData,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_checkoutData = $checkoutData;
        parent::__construct($context, $data);
    }

    /**
     * Retrieves HTML code of "Configure" button
     *
     * @return string
     */
    public function getConfigureButtonHtml()
    {
        $canConfigure = $this->getProduct()->canConfigure() && !$this->getItem()->getIsConfigureDisabled();
        $productId = $this->_jsonEncoder->encode($this->getProduct()->getId());
        $itemSku = $this->_jsonEncoder->encode($this->getItem()->getSku());

        /* @var $button \Magento\Backend\Block\Widget\Button */
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class,
            '',
            [
                'data' => [
                    'class' => $canConfigure ? 'action-configure' : 'action-configure action-disabled',
                    'onclick' => $canConfigure ? "addBySku.configure({$productId}, {$itemSku})" : '',
                    'disabled' => !$canConfigure,
                    'label' => __('Configure'),
                    'type' => 'button',
                ]
            ]
        );

        return $button->toHtml();
    }

    /**
     * Retrieve HTML name for element
     *
     * @return string
     */
    public function getSourceId()
    {
        return $this->_prepareLayout()->getLayout()->getBlock('sku_error_grid')->getId();
    }

    /**
     * Returns error message of the item
     *
     * @param \Magento\Framework\DataObject $item
     * @return string
     *
     * @see \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_* constants for $code
     */
    public function getErrorMessage($item)
    {
        return $this->_checkoutData->getMessageByItem($item);
    }
}
