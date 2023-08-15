<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Block\Adminhtml\Scheduled\Operation;

/**
 * Scheduled operation create/edit form container
 *
 * @api
 * @author      Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Import export data
     *
     * @var \Magento\ScheduledImportExport\Helper\Data
     */
    protected $_importExportData = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\ScheduledImportExport\Model\Scheduled\OperationFactory
     */
    protected $_operationFactory;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\ScheduledImportExport\Model\Scheduled\OperationFactory $operationFactory
     * @param \Magento\ScheduledImportExport\Helper\Data $importExportData
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\ScheduledImportExport\Model\Scheduled\OperationFactory $operationFactory,
        \Magento\ScheduledImportExport\Helper\Data $importExportData,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_operationFactory = $operationFactory;
        $this->_coreRegistry = $registry;
        $this->_importExportData = $importExportData;
        parent::__construct($context, $data);
    }

    /**
     * Initialize operation form container.
     * Create operation instance from database and set it to register.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Magento_ScheduledImportExport';
        $this->_mode = 'edit';
        $this->_controller = 'adminhtml_scheduled_operation';

        $operationId = (int)$this->getRequest()->getParam($this->_objectId);
        /** @var \Magento\ScheduledImportExport\Model\Scheduled\Operation $operation */
        $operation = $this->_operationFactory->create();
        if ($operationId) {
            $operation->load($operationId);
        } else {
            $operation->setOperationType($this->getRequest()->getParam('type'))->setStatus(true);
        }
        $this->_coreRegistry->register('current_operation', $operation);

        parent::_construct();
    }

    /**
     * Prepare page layout.
     * Set form object to container.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return $this
     */
    protected function _prepareLayout()
    {
        $operation = $this->_coreRegistry->registry('current_operation');
        $blockName = 'Magento\\ScheduledImportExport\\Block\\Adminhtml\\Scheduled\\Operation\\Edit\\Form\\' . ucfirst(
            $operation->getOperationType()
        );
        $formBlock = $this->getLayout()->createBlock($blockName);
        if ($formBlock) {
            $this->setChild('form', $formBlock);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please correct the scheduled operation type.')
            );
        }

        $this->buttonList->update(
            'delete',
            'onclick',
            'deleteConfirm(\'' . $this->_importExportData->getConfirmationDeleteMessage(
                $operation->getOperationType()
            ) . '\', \'' . $this->getDeleteUrl() . '\')'
        );

        parent::_prepareLayout();
        return $this;
    }

    /**
     * Get operation delete url
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl(
            'adminhtml/*/delete',
            [
                $this->_objectId => $this->getRequest()->getParam($this->_objectId),
                'type' => $this->_coreRegistry->registry('current_operation')->getOperationType()
            ]
        );
    }

    /**
     * Get page header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        $operation = $this->_coreRegistry->registry('current_operation');
        if ($operation->getId()) {
            $action = 'edit';
        } else {
            $action = 'new';
        }
        return $this->_importExportData->getOperationHeaderText($operation->getOperationType(), $action);
    }
}
