<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General;

/**
 * Request Details Block at RMA page
 *
 * @api
 * @author     Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Returnaddress extends \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\AbstractGeneral
{
    /**
     * Rma data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaData = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Rma\Helper\Data $rmaData,
        array $data = []
    ) {
        $this->_rmaData = $rmaData;
        parent::__construct($context, $registry, $data);
    }

    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        $order = $this->_coreRegistry->registry('current_order');
        $rma = $this->_coreRegistry->registry('current_rma');
        if ($order && $order->getId()) {
            $this->setStoreId($order->getStoreId());
        } elseif ($rma && $rma->getId()) {
            $this->setStoreId($rma->getStoreId());
        }
    }

    /**
     * Get Customer Email
     *
     * @return string
     */
    public function getReturnAddress()
    {
        return $this->_rmaData->getReturnAddress('html', [], $this->getStoreId());
    }
}
