<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Adminhtml\Customer;

/**
 * @api
 * @since 100.0.2
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize form
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Magento_GiftRegistry';
        $this->_controller = 'adminhtml_customer';

        parent::_construct();

        $this->buttonList->remove('reset');
        $this->buttonList->remove('save');

        $confirmMessage = __('Are you sure you want to delete this gift registry?');
        $this->buttonList->update('delete', 'label', __('Delete Registry'));
        $this->buttonList->update(
            'delete',
            'onclick',
            'deleteConfirm(\''
            . $this->escapeJs($confirmMessage) . '\', \''
            . $this->escapeUrl($this->getDeleteUrl()) . '\')'
        );
    }

    /**
     * Return form header text
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getHeaderText()
    {
        $entity = $this->_coreRegistry->registry('current_giftregistry_entity');
        if ($entity->getId()) {
            return $this->escapeHtml($entity->getTitle());
        }
        return __('Gift Registry Entity');
    }

    /**
     * Retrieve form back url
     *
     * @return string
     */
    public function getBackUrl()
    {
        $customerId = null;
        if ($this->_coreRegistry->registry('current_giftregistry_entity')) {
            $customerId = $this->_coreRegistry->registry('current_giftregistry_entity')->getCustomerId();
        }
        return $this->getUrl('customer/index/edit', ['id' => $customerId, 'active_tab' => 'giftregistry']);
    }
}
