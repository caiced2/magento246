<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller;

abstract class Tracking extends \Magento\Framework\App\Action\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Http response file factory
     *
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileResponseFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileResponseFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileResponseFactory
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_fileResponseFactory = $fileResponseFactory;
        parent::__construct($context);
    }

    /**
     * Check order view availability
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     */
    protected function _canViewRma($rma)
    {
        if (!$this->_objectManager->get(\Magento\Customer\Model\Session::class)->isLoggedIn()) {
            $currentOrder = $this->_coreRegistry->registry('current_order');
            if ($rma->getOrderId() && $rma->getOrderId() === $currentOrder->getId()) {
                return true;
            }
            return false;
        } else {
            return true;
        }
    }

    /**
     * Try to load valid rma by entity_id and register it
     *
     * @param int $entityId
     * @return bool|void
     */
    protected function _loadValidRma($entityId = null)
    {
        if (!$this->_objectManager->get(\Magento\Customer\Model\Session::class)->isLoggedIn()
            &&
            true !== $this->_objectManager->get(\Magento\Sales\Helper\Guest::class)->loadValidOrder($this->_request)
        ) {
            return;
        }

        if (null === $entityId) {
            $entityId = (int)$this->getRequest()->getParam('entity_id');
        }

        if (!$entityId) {
            $this->_forward('noroute');
            return false;
        }

        /** @var $rma \Magento\Rma\Model\Rma */
        $rma = $this->_objectManager->create(\Magento\Rma\Model\Rma::class)->load($entityId);
        if ($this->_canViewRma($rma)) {
            $this->_coreRegistry->register('current_rma', $rma);
            return true;
        } else {
            $this->_redirect('*/*/returns');
        }
        return false;
    }
}
