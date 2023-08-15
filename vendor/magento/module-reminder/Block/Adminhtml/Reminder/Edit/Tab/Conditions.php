<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Block\Adminhtml\Reminder\Edit\Tab;

use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;

/**
 * Reminder rules edit form conditions
 */
class Conditions extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Backend\Block\Widget\Form\Renderer\Fieldset
     */
    protected $_fieldsetBlock;

    /**
     * @var \Magento\Rule\Block\Conditions
     */
    protected $_conditionsBlock;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $fieldsetBlock
     * @param \Magento\Rule\Block\Conditions $conditionsBlock
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $fieldsetBlock,
        \Magento\Rule\Block\Conditions $conditionsBlock,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_fieldsetBlock = $fieldsetBlock;
        $this->_conditionsBlock = $conditionsBlock;
    }

    /**
     * Prepare conditions form
     *
     * @return Form
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $model = $this->_coreRegistry->registry('current_reminder_rule');

        $renderer = $this->getLayout()->createBlock(Fieldset::class);
        $renderer->setTemplate(
            'Magento_CatalogRule::promo/fieldset.phtml'
        )->setNewChildUrl(
            $this->getUrl('adminhtml/reminder/newConditionHtml/form/rule_conditions_fieldset')
        );
        $fieldset = $form->addFieldset(
            'rule_conditions_fieldset',
            [
                'legend' => __('Conditions'),
                'comment' => __('You need to set at least one condition for this rule to work.')
            ]
        )->setRenderer(
            $renderer
        );

        $fieldset->addField(
            'conditions',
            'text',
            ['name' => 'conditions', 'required' => true]
        )->setRule(
            $model
        )->setRenderer(
            $this->_conditionsBlock
        );

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
