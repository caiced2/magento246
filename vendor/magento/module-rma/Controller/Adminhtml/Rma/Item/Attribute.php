<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma\Item;

abstract class Attribute extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Rma::rma_attribute';

    /**
     * RMA Item Entity Type instance
     *
     * @var \Magento\Eav\Model\Entity\Type
     */
    protected $_entityType;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $websiteFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Store\Model\WebsiteFactory $websiteFactory
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->websiteFactory = $websiteFactory;
        parent::__construct($context);
    }

    /**
     * Return RMA Item Entity Type instance
     *
     * @return \Magento\Eav\Model\Entity\Type
     */
    protected function _getEntityType()
    {
        if ($this->_entityType === null) {
            $this->_entityType = $this->_objectManager->get(
                \Magento\Eav\Model\Config::class
            )->getEntityType('rma_item');
        }
        return $this->_entityType;
    }

    /**
     * Load layout, set breadcrumbs
     *
     * @return \Magento\Rma\Controller\Adminhtml\Rma\Item\Attribute
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Magento_Rma::sales_magento_rma_rma_item_attribute'
        )->_addBreadcrumb(
            __('RMA'),
            __('RMA')
        )->_addBreadcrumb(
            __('Manage RMA Item Attributes'),
            __('Manage RMA Item Attributes')
        );
        return $this;
    }

    /**
     * Retrieve RMA item attribute object
     *
     * @return \Magento\Rma\Model\Item\Attribute
     */
    protected function _initAttribute()
    {
        /** @var $attribute \Magento\Rma\Model\Item\Attribute */
        $attribute = $this->_objectManager->create(\Magento\Rma\Model\Item\Attribute::class);
        $website = $this->getRequest()->getParam('website') ?: $this->websiteFactory->create();
        $attribute->setWebsite($website);
        return $attribute;
    }
}
