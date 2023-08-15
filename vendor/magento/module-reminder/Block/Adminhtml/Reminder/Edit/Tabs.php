<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Block\Adminhtml\Reminder\Edit;

use Magento\Framework\Registry;

/**
 * Reminder rules edit tabs block
 *
 * @api
 * @since 100.0.2
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Core registry
     *
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }

    /**
     * Initialize form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('magento_reminder_rule_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Email Reminder Rule'));
    }

    /**
     * Add tab sections
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->addTab(
            'general_section',
            [
                'label' => __('Rule Information'),
                'content' => $this->getLayout()->createBlock(
                    \Magento\Reminder\Block\Adminhtml\Reminder\Edit\Tab\General::class,
                    'adminhtml_reminder_edit_tab_general'
                )->toHtml()
            ]
        );

        $this->addTab(
            'conditions_section',
            [
                'label' => __('Conditions'),
                'content' => $this->getLayout()->createBlock(
                    \Magento\Reminder\Block\Adminhtml\Reminder\Edit\Tab\Conditions::class,
                    'adminhtml_reminder_edit_tab_conditions'
                )->toHtml()
            ]
        );

        $this->addTab(
            'template_section',
            [
                'label' => __('Emails and Labels'),
                'content' => $this->getLayout()->createBlock(
                    \Magento\Reminder\Block\Adminhtml\Reminder\Edit\Tab\Templates::class,
                    'adminhtml_reminder_edit_tab_templates'
                )->toHtml()
            ]
        );

        $rule = $this->_coreRegistry->registry('current_reminder_rule');
        if ($rule && $rule->getId()) {
            $this->addTab(
                'matched_customers',
                [
                    'label' => __('Matched Customers'),
                    'url' => $this->getUrl('adminhtml/*/customerGrid', ['rule_id' => $rule->getId()]),
                    'class' => 'ajax'
                ]
            );
        }

        return parent::_beforeToHtml();
    }
}
