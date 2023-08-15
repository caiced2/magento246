<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Adminhtml\Giftregistry\Edit\Tab;

/**
 * @codeCoverageIgnore
 */
class General extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $sourceYesNo;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Config\Model\Config\Source\Yesno $sourceYesNo
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\Yesno $sourceYesNo,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->sourceYesNo = $sourceYesNo;
    }

    /**
     * Return current gift registry type instance
     *
     * @return \Magento\GiftRegistry\Model\Type
     */
    public function getType()
    {
        return $this->_coreRegistry->registry('current_giftregistry_type');
    }

    /**
     * Prepares layout and set element renderer
     *
     * @return void
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($this->getLayout()->hasElement($this->getNameInLayout() . '_element')) {
            $this->getLayout()->unsetElement($this->getNameInLayout() . '_element');
        }
        \Magento\Framework\Data\Form::setFieldsetElementRenderer(
            $this->getLayout()->createBlock(
                \Magento\GiftRegistry\Block\Adminhtml\Giftregistry\Form\Renderer\Element::class,
                $this->getNameInLayout() . '_element'
            )
        );
    }

    /**
     * Prepare general properties form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setFieldNameSuffix('type');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General Information')]);

        if ($this->getType()->getId()) {
            $fieldset->addField('type_id', 'hidden', ['name' => 'type_id']);
        }

        $fieldset->addField(
            'code',
            'text',
            ['name' => 'code', 'label' => __('Code'), 'required' => true, 'class' => 'validate-code']
        );

        $fieldset->addField(
            'label',
            'text',
            ['name' => 'label', 'label' => __('Label'), 'required' => true, 'scope' => 'store']
        );

        $fieldset->addField(
            'sort_order',
            'text',
            ['name' => 'sort_order', 'label' => __('Sort Order'), 'scope' => 'store']
        );

        $fieldset->addField(
            'is_listed',
            'select',
            [
                'label' => __('Is Listed'),
                'name' => 'is_listed',
                'values' => $this->sourceYesNo->toOptionArray(),
                'scope' => 'store'
            ]
        );

        $form->setValues($this->getType()->getData());
        $this->setForm($form);
        $form->setDataObject($this->getType());

        return parent::_prepareForm();
    }
}
