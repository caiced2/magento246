<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Block\Adminhtml\Customer\Edit\Tab\Reward\Management;

use Magento\Customer\Controller\RegistryConstants;

/**
 * Reward update points form
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Update extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Core system store model
     *
     * @var \Magento\Store\Model\System\StoreFactory
     */
    protected $_storeFactory;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\StoreFactory $storeFactory
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\StoreFactory $storeFactory,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        array $data = []
    ) {
        $this->_storeFactory = $storeFactory;
        $this->customerRegistry = $customerRegistry;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Getter
     *
     * @return \Magento\Customer\Model\Customer
     * @codeCoverageIgnore
     */
    public function getCustomer()
    {
        $customerId = $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        return $this->customerRegistry->retrieve($customerId);
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @codeCoverageIgnore
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('reward_');
        $form->setFieldNameSuffix('reward');
        $fieldset = $form->addFieldset('update_fieldset', ['legend' => __('Update Reward Points Balance')]);

        if (!$this->_storeManager->isSingleStoreMode()) {
            $fieldset->addField(
                'store',
                'select',
                [
                    'name' => 'store_id',
                    'title' => __('Store'),
                    'label' => __('Store'),
                    'values' => $this->_getStoreValues(),
                    'data-form-part' => $this->getData('target_form')
                ]
            );
        }

        $fieldset->addField(
            'points_delta',
            'text',
            [
                'name' => 'points_delta',
                'title' => __('Update Points'),
                'label' => __('Update Points'),
                'note' => __('Enter a negative number to subtract from the balance.'),
                'data-form-part' => $this->getData('target_form')
            ]
        );

        $fieldset->addField(
            'comment',
            'text',
            [
                'name' => 'comment',
                'title' => __('Comment'),
                'label' => __('Comment'),
                'data-form-part' => $this->getData('target_form')
            ]
        );

        $fieldset = $form->addFieldset('notification_fieldset', ['legend' => __('Reward Points Notifications')]);

        $fieldset->addField(
            'update_notification',
            'checkbox',
            [
                'name' => 'reward_update_notification',
                'label' => __('Subscribe for Balance Updates'),
                'checked' => (bool)$this->getCustomer()->getRewardUpdateNotification(),
                'value' => 1,
                'data-form-part' => $this->getData('target_form')
            ]
        );

        $fieldset->addField(
            'warning_notification',
            'checkbox',
            [
                'name' => 'reward_warning_notification',
                'label' => __('Subscribe for Points Expiration Notifications'),
                'checked' => (bool)$this->getCustomer()->getRewardWarningNotification(),
                'value' => 1,
                'data-form-part' => $this->getData('target_form')
            ]
        );

        $this->updateFromSession($form, $this->getCustomer()->getId());

        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Update form elements from session data
     *
     * @param \Magento\Framework\Data\Form $form
     * @param int $customerId
     * @return void
     */
    protected function updateFromSession(\Magento\Framework\Data\Form $form, $customerId)
    {
        $data = $this->_backendSession->getCustomerFormData();
        if (!empty($data)) {
            $dataCustomerId = isset($data['customer']['entity_id']) ? $data['customer']['entity_id'] : null;
            if (isset($data['reward']) && $customerId == $dataCustomerId) {
                if (isset($data['reward']['reward_update_notification'])) {
                    $form->getElement('update_notification')
                        ->setIsChecked($data['reward']['reward_update_notification']);
                    unset($data['reward']['reward_update_notification']);
                }
                if (isset($data['reward']['reward_warning_notification'])) {
                    $form->getElement('warning_notification')
                        ->setIsChecked($data['reward']['reward_warning_notification']);
                    unset($data['reward']['reward_warning_notification']);
                }
                $form->addValues($data['reward']);
            }
        }
    }

    /**
     * Retrieve source values for store drop-dawn
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function _getStoreValues()
    {
        $customer = $this->getCustomer();
        if (!$customer->getWebsiteId() ||
            $this->_storeManager->hasSingleStore() ||
            $customer->getSharingConfig()->isGlobalScope()
        ) {
            return $this->_storeFactory->create()->getStoreValuesForForm();
        }

        $stores = $this->_storeFactory->create()->getStoresStructure(
            false,
            [],
            [],
            [$customer->getWebsiteId()]
        );
        $values = [];

        $nonEscapableNbspChar = html_entity_decode('&#160;', ENT_NOQUOTES, 'UTF-8');
        foreach ($stores as $websiteId => $website) {
            $values[] = ['label' => $website['label'], 'value' => []];
            if (isset($website['children']) && is_array($website['children'])) {
                foreach ($website['children'] as $groupId => $group) {
                    if (isset($group['children']) && is_array($group['children'])) {
                        $options = [];
                        foreach ($group['children'] as $storeId => $store) {
                            $options[] = [
                                'label' => str_repeat($nonEscapableNbspChar, 4) . $store['label'],
                                'value' => $store['value'],
                            ];
                        }
                        $values[] = [
                            'label' => str_repeat($nonEscapableNbspChar, 4) . $group['label'],
                            'value' => $options,
                        ];
                    }
                }
            }
        }
        return $values;
    }
}
