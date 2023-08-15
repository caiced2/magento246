<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller;

use Magento\Rma\Model\Rma;

abstract class Guest extends \Magento\Framework\App\Action\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Rma\Helper\Data
     */
    protected $rmaHelper;

    /**
     * @var \Magento\Sales\Helper\Guest
     */
    protected $salesGuestHelper;

    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Rma\Helper\Data $rmaHelper
     * @param \Magento\Sales\Helper\Guest $salesGuestHelper
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Rma\Helper\Data $rmaHelper,
        \Magento\Sales\Helper\Guest $salesGuestHelper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
        $this->rmaHelper = $rmaHelper;
        $this->salesGuestHelper = $salesGuestHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * Check order view availability
     *
     * @param   \Magento\Rma\Model\Rma $rma
     * @return  bool
     */
    protected function _canViewRma($rma)
    {
        $currentOrder = $this->_coreRegistry->registry('current_order');
        if ($rma->getOrderId() && $rma->getOrderId() === $currentOrder->getId()) {
            return true;
        }
        return false;
    }

    /**
     * Try to load valid rma by entity_id and register it
     *
     * @param int $entityId
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\Result\Forward|bool
     */
    protected function _loadValidRma($entityId = null)
    {
        if (!$this->rmaHelper->isEnabled()) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $result = $this->salesGuestHelper->loadValidOrder($this->_request);
        if ($result instanceof \Magento\Framework\Controller\ResultInterface) {
            return $result;
        }

        if (null === $entityId) {
            $entityId = (int)$this->getRequest()->getParam('entity_id');
        }

        if (!$entityId) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }
        /** @var $rma \Magento\Rma\Model\Rma */
        $rma = $this->_objectManager->create(\Magento\Rma\Model\Rma::class)->load($entityId);

        if ($this->_canViewRma($rma)) {
            $this->_coreRegistry->register('current_rma', $rma);
            return true;
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/returns');
    }
}
