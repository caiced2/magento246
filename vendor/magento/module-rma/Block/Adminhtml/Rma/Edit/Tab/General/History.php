<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General;

/**
 * Comments History Block at RMA page
 *
 * @api
 * @since 100.0.2
 */
class History extends \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\AbstractGeneral
{
    /**
     * Rma config model
     *
     * @var \Magento\Rma\Model\Config
     */
    protected $_rmaConfig;

    /**
     * Rma status history collection
     *
     * @var \Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Rma\Model\Config $rmaConfig
     * @param \Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Rma\Model\Config $rmaConfig,
        \Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->_rmaConfig = $rmaConfig;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context, $registry, $data);
    }

    /**
     * Prepare child blocks
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $onclick = "submitAndReloadArea($('rma-history-block').parentNode, '" . $this->getSubmitUrl() . "')";
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            ['label' => __('Submit Comment'), 'class' => 'action-save action-secondary', 'onclick' => $onclick]
        );
        $this->setChild('submit_button', $button);

        return parent::_prepareLayout();
    }

    /**
     * Get config value - is Enabled RMA Comments Email
     *
     * @return bool
     */
    public function canSendCommentEmail()
    {
        $this->_rmaConfig->init($this->_rmaConfig->getRootCommentEmail(), $this->getOrder()->getStore());
        return $this->_rmaConfig->isEnabled();
    }

    /**
     * Get config value - is Enabled RMA Email
     *
     * @return bool
     */
    public function canSendConfirmationEmail()
    {
        $this->_rmaConfig->init($this->_rmaConfig->getRootRmaEmail(), $this->getOrder()->getStore());
        return $this->_rmaConfig->isEnabled();
    }

    /**
     * Get URL to add comment action
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('adminhtml/*/addComment', ['id' => $this->getRmaData('entity_id')]);
    }

    /**
     * Get comments
     *
     * @return \Magento\Rma\Model\ResourceModel\Rma\Status\History\Collection
     */
    public function getComments()
    {
        /** @var $collection \Magento\Rma\Model\ResourceModel\Rma\Status\History\Collection */
        $collection = $this->_collectionFactory->create();
        return $collection->addFieldToFilter('rma_entity_id', $this->_coreRegistry->registry('current_rma')->getId());
    }
}
