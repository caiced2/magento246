<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma;

/**
 * @api
 * @since 100.0.2
 */
class NewRma extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Rma data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaData = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_rmaData = $rmaData;
        parent::__construct($context, $data);
    }

    /**
     * Initialize RMA new page. Set management buttons
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_rma';
        $this->_blockGroup = 'Magento_Rma';

        parent::_construct();

        $this->buttonList->update('reset', 'label', __('Cancel'));
        $this->buttonList->update('reset', 'class', 'cancel');

        $link = $this->getUrl('adminhtml/*/');
        $order = $this->_coreRegistry->registry('current_order');

        if ($order && $order->getId()) {
            $orderId = $order->getId();
            $referer = $this->getRequest()->getServer('HTTP_REFERER');

            if (strpos($referer, 'customer') !== false) {
                $link = $this->getUrl(
                    'customer/index/edit/',
                    ['id' => $order->getCustomerId(), 'active_tab' => 'orders']
                );
            }
        } else {
            return;
        }

        if ($this->_rmaData->canCreateRma($orderId, true)) {
            $this->buttonList->update('reset', 'onclick', "setLocation('" . $link . "')");
            $this->buttonList->update('save', 'label', __('Submit Returns'));
        } else {
            $this->buttonList->update('reset', 'onclick', "setLocation('" . $link . "')");
            $this->buttonList->remove('save');
        }
        $this->buttonList->remove('back');
    }

    /**
     * Get header text for RMA edit page
     *
     * @return string
     */
    public function getHeaderText()
    {
        return $this->getLayout()->createBlock(\Magento\Rma\Block\Adminhtml\Rma\Create\Header::class)->toHtml();
    }

    /**
     * Get form action URL
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl(
            'adminhtml/*/save',
            ['order_id' => $this->_coreRegistry->registry('current_order')->getId()]
        );
    }
}
