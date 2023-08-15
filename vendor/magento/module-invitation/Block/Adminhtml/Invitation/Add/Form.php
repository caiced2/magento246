<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Invitation\Block\Adminhtml\Invitation\Add;

use Magento\Customer\Api\GroupManagementInterface as CustomerGroupManagement;
use Magento\Customer\Model\Customer\Attribute\Source\GroupSourceLoggedInOnlyInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Invitation create form
 *
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Magento Store
     *
     * @var \Magento\Store\Model\System\Store
     */
    protected $_store;

    /**
     * @deprecated 100.2.0
     * @var CustomerGroupManagement
     */
    protected $customerGroupManagement;

    /**
     * @deprecated 100.2.0
     * @var \Magento\Framework\Convert\DataObject
     */
    protected $_objectConverter;

    /**
     * @var GroupSourceLoggedInOnlyInterface
     */
    private $groupSourceLoggedInOnly;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $store
     * @param CustomerGroupManagement $customerGroupManagement
     * @param \Magento\Framework\Convert\DataObject $objectConverter
     * @param array $data
     * @param GroupSourceLoggedInOnlyInterface $groupSourceForLoggedInCustomers
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $store,
        CustomerGroupManagement $customerGroupManagement,
        \Magento\Framework\Convert\DataObject $objectConverter,
        array $data = [],
        GroupSourceLoggedInOnlyInterface $groupSourceForLoggedInCustomers = null
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_store = $store;
        $this->customerGroupManagement = $customerGroupManagement;
        $this->_objectConverter = $objectConverter;
        $this->groupSourceLoggedInOnly = $groupSourceForLoggedInCustomers
            ?: ObjectManager::getInstance()->get(GroupSourceLoggedInOnlyInterface::class);
    }

    /**
     * Return invitation form action url
     *
     * @return string
     */
    public function getActionUrl()
    {
        return $this->getUrl('invitations/*/save', ['_current' => true]);
    }

    /**
     * Prepare invitation form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getActionUrl(), 'method' => 'post']]
        );

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Invitations Information'), 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'email',
            'textarea',
            [
                'label' => __('Enter Each Email on New Line'),
                'required' => true,
                'class' => 'validate-emails',
                'name' => 'email'
            ]
        );

        $fieldset->addField('message', 'textarea', ['label' => __('Message'), 'name' => 'message']);

        if (!$this->_storeManager->isSingleStoreMode()) {
            $field = $fieldset->addField(
                'store_id',
                'select',
                [
                    'label' => __('Send From'),
                    'required' => true,
                    'name' => 'store_id',
                    'values' => $this->_store->getStoreValuesForForm()
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                \Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element::class
            );
            $field->setRenderer($renderer);
        }

        $groups = $this->groupSourceLoggedInOnly->toOptionArray();

        $fieldset->addField(
            'group_id',
            'select',
            ['label' => __('Invitee Group'), 'required' => true, 'name' => 'group_id', 'values' => $groups]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        $form->setValues($this->_backendSession->getInvitationFormData());

        return parent::_prepareForm();
    }
}
