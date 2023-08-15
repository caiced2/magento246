<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Block\Adminhtml\Giftregistry;

/**
 * Gift Registry Edit Block
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
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Magento_GiftRegistry';
        $this->_controller = 'adminhtml_giftregistry';

        parent::_construct();

        if ($this->_coreRegistry->registry('current_giftregistry_type')) {
            $this->buttonList->update('save', 'label', __('Save'));
            $this->buttonList->update(
                'save',
                'data_attribute',
                ['mage-init' => ['button' => ['event' => 'save', 'target' => '#edit_form']]]
            );

            $confirmMessage = __(
                "If you delete this gift registry type, you also delete customer registries that use this type. "
                . "Do you want to continue?"
            );
            $this->buttonList->update('delete', 'label', __('Delete'));
            $this->buttonList->update(
                'delete',
                'onclick',
                'deleteConfirm(\'' . $this->escapeJs($confirmMessage) . '\', \''
                . $this->escapeUrl($this->getDeleteUrl()) . '\', {data: {}})'
            );

            $this->buttonList->add(
                'save_and_continue_edit',
                [
                    'class' => 'save',
                    'label' => __('Save and Continue Edit'),
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                3
            );
        }
    }

    /**
     * Return form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $type = $this->_coreRegistry->registry('current_giftregistry_type');
        if ($type->getId()) {
            return __("Edit '%1' Gift Registry Type", $this->escapeHtml($type->getLabel()));
        } else {
            return __('New Gift Registry Type');
        }
    }

    /**
     * Return save url
     *
     * @return string
     */
    public function getSaveUrl()
    {
        $type = $this->_coreRegistry->registry('current_giftregistry_type');
        return $this->getUrl('adminhtml/*/save', ['id' => $type->getId(), 'store' => $type->getStoreId()]);
    }
}
