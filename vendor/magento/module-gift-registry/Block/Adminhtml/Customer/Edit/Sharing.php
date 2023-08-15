<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Adminhtml\Customer\Edit;

/**
 * @codeCoverageIgnore
 */
class Sharing extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->systemStore = $systemStore;
    }

    /**
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getActionUrl(),
                    'method' => 'post',
                    'class' => 'form-sharing-info'
                ]
            ]
        );

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Sharing Information'), 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'emails',
            'text',
            [
                'label' => __('Emails'),
                'required' => true,
                'class' => 'validate-emails',
                'name' => 'emails',
                'note' => 'Enter list of emails, comma-separated.'
            ]
        );

        if (!$this->_storeManager->isSingleStoreMode()) {
            $fieldset->addField(
                'store_id',
                'select',
                [
                    'label' => __('Send From'),
                    'required' => true,
                    'name' => 'store_id',
                    'values' => $this->systemStore->getStoreValuesForForm()
                ]
            );
        }

        $fieldset->addField(
            'message',
            'textarea',
            [
                'label' => __('Message'),
                'name' => 'message',
                'class' => 'sharing-info-textarea',
                'after_element_html' => $this->getShareButton()
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        $form->setDataObject();

        return parent::_prepareForm();
    }

    /**
     * Return sharing form action url
     *
     * @return string
     */
    public function getActionUrl()
    {
        return $this->getUrl('adminhtml/*/share', ['_current' => true]);
    }

    /**
     * Create button
     *
     * @return string
     */
    public function getShareButton()
    {
        return $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->addData(
            ['id' => '', 'label' => __('Share Gift Registry'), 'type' => 'submit']
        )->toHtml();
    }
}
