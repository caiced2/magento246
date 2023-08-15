<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Block\Adminhtml\Banner;

/**
 * @api
 * @since 100.0.2
 * @deprecated Banner form configuration has been moved on ui component declaration
 * @see app/code/Magento/Banner/view/adminhtml/ui_component/banner_form.xml
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_registry = null;

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
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize banner edit page. Set management buttons
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_banner';
        $this->_blockGroup = 'Magento_Banner';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save Dynamic Block'));
        $this->buttonList->update('delete', 'label', __('Delete Dynamic Block'));

        $this->buttonList->add(
            'save_and_edit_button',
            [
                'label' => __('Save and Continue Edit'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],
                ]
            ],
            100
        );
    }

    /**
     * Get current loaded banner ID
     *
     * @return mixed
     */
    public function getBannerId()
    {
        return $this->_registry->registry('current_banner')->getId();
    }

    /**
     * Get header text for banner edit page
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getHeaderText()
    {
        if ($this->_registry->registry('current_banner')->getId()) {
            return $this->escapeHtml($this->_registry->registry('current_banner')->getName());
        } else {
            return __('New Dynamic Block');
        }
    }

    /**
     * Get form action URL
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('adminhtml/*/save');
    }
}
