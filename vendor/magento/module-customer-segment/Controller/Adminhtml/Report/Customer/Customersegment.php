<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Controller\Adminhtml\Report\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\CustomerSegment\Helper\Data;
use Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory;
use Magento\CustomerSegment\Model\Segment;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Registry;

/**
 * Customer Segment reports controller
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class Customersegment extends Action
{
    /**
     * @inheritdoc
     */
    const ADMIN_RESOURCE = 'Magento_CustomerSegment::segment';
    /**
     * Admin session
     *
     * @var Session
     */
    protected $_adminSession;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var FileFactory
     */
    protected $_fileFactory;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Registry $coreRegistry
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        Registry $coreRegistry,
        FileFactory $fileFactory
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * Init layout and adding breadcrumbs
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Magento_CustomerSegment::report_customers_segment'
        )->_addBreadcrumb(
            __('Reports'),
            __('Reports')
        )->_addBreadcrumb(
            __('Customers'),
            __('Customers')
        );
        return $this;
    }

    /**
     * Initialize Customer Segmen Model
     *
     * Add error to session storage if object was not loaded
     *
     * @param bool $outputMessage
     * @return Segment|false
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _initSegment($outputMessage = true)
    {
        $segmentId = $this->getRequest()->getParam('segment_id', 0);
        $segmentIds = $this->getRequest()->getParam('massaction');
        if ($segmentIds) {
            $this->_getAdminSession()->setMassactionIds(
                $segmentIds
            )->setViewMode(
                $this->getRequest()->getParam('view_mode')
            );
        }

        /* @var $segment Segment */
        $segment = $this->_objectManager->create(Segment::class);
        if ($segmentId) {
            $segment->load($segmentId);
        }
        if ($this->_getAdminSession()->getMassactionIds()) {
            $segment->setMassactionIds($this->_getAdminSession()->getMassactionIds());
            $segment->setViewMode($this->_getAdminSession()->getViewMode());
        }
        if (!$segment->getId() && !$segment->getMassactionIds()) {
            if ($outputMessage) {
                $this->messageManager->addError(__('Please request the correct customer segment.'));
            }
            return false;
        }
        $this->_coreRegistry->register('current_customer_segment', $segment);

        $websiteIds = $this->getRequest()->getParam('website_ids');
        if ($websiteIds !== null && empty($websiteIds)) {
            $websiteIds = null;
        } elseif ($websiteIds !== null && !empty($websiteIds)) {
            $websiteIds = explode(',', $websiteIds);
        }
        $this->_coreRegistry->register('filter_website_ids', $websiteIds);

        return $segment;
    }

    /**
     * Retrieve admin session model
     *
     * @return Session
     */
    protected function _getAdminSession()
    {
        if ($this->_adminSession === null) {
            $this->_adminSession = $this->_objectManager->create(Session::class);
        }
        return $this->_adminSession;
    }

    /**
     * Check the permission to run it
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return parent::_isAllowed() && $this->_objectManager->get(Data::class)->isEnabled();
    }
}
