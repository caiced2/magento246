<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Block\Adminhtml\Giftwrapping;

/**
 * Gift Wrapping Edit Block
 * @api
 * @since 100.0.2
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry|null
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
        $this->_controller = 'adminhtml_giftwrapping';
        $this->_blockGroup = 'Magento_GiftWrapping';

        parent::_construct();

        $this->buttonList->remove('reset');

        $this->buttonList->add(
            'save_and_continue_edit',
            [
                'class' => 'save',
                'label' => __('Save and Continue Edit'),
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],
                ]
            ],
            3
        );

        $giftWrapping = $this->_coreRegistry->registry('current_giftwrapping_model');
        if ($giftWrapping && $giftWrapping->getId()) {
            $confirmMessage = __('Are you sure you want to delete this gift wrapping?');
            $this->buttonList->update(
                'delete',
                'onclick',
                'deleteConfirm(\'' . $this->escapeJs($confirmMessage) . '\', \''
                . $this->escapeUrl($this->getDeleteUrl()) . '\', {data: {}})'
            );
        }
        $this->_formScripts[] = "
                // Temporary solution will be replaced after refactoring Gift Wrapping functionality
                function uploadImagesForPreview() {
                    var fform = jQuery('#edit_form');
                    fform.find('input, select, textarea').forEach(function() {
                        jQuery(this).attr('type') === 'file' ?
                            jQuery(this).addClass('required-entry') :
                            jQuery(this).addClass('ignore-validate temp-ignore-validate');
                    });
                    fform.on('invalid-form.validate', function() {
                        fform.find('.temp-ignore-validate').removeClass('ignore-validate temp-ignore-validate');
                        fform.find('[type=\"file\"]').removeClass('required-entry');
                        fform.off('invalid-form.validate');
                    });
                    fform.triggerHandler('save', [{action: '" .
            $this->getUploadUrl() .
            "'}]);
                }
            ";
    }

    /**
     * Return form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $wrapping = $this->_coreRegistry->registry('current_giftwrapping_model');
        if ($wrapping->getId()) {
            $title = $this->escapeHtml($wrapping->getDesign());
            return __('Edit Gift Wrapping "%1"', $title);
        } else {
            return __('New Gift Wrapping');
        }
    }

    /**
     * Return save url (used for Save and Continue button)
     *
     * @return string
     */
    public function getSaveUrl()
    {
        $wrapping = $this->_coreRegistry->registry('current_giftwrapping_model');

        if ($wrapping) {
            $url = $this->getUrl(
                'adminhtml/*/save',
                ['id' => $wrapping->getId(), 'store' => $wrapping->getStoreId()]
            );
        } else {
            $url = $this->getUrl('adminhtml/*/save');
        }
        return $url;
    }

    /**
     * Return upload url (used for Upload button)
     *
     * @return string
     */
    public function getUploadUrl()
    {
        $wrapping = $this->_coreRegistry->registry('current_giftwrapping_model');
        $params = [];
        if ($wrapping) {
            $params['store'] = $wrapping->getStoreId();
            if ($wrapping->getId()) {
                $params['id'] = $wrapping->getId();
            }
        }

        return $this->getUrl('adminhtml/*/upload', $params);
    }
}
