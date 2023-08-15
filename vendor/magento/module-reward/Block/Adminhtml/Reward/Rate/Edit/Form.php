<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Reward rate edit form
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @codeCoverageIgnore
 */
namespace Magento\Reward\Block\Adminhtml\Reward\Rate\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Reward\Model\Source\WebsiteFactory
     */
    protected $_websitesFactory;

    /**
     * @var \Magento\Reward\Model\Source\Customer\GroupsFactory
     */
    protected $_groupsFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Reward\Model\Source\WebsiteFactory $websitesFactory
     * @param \Magento\Reward\Model\Source\Customer\GroupsFactory $groupsFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Reward\Model\Source\WebsiteFactory $websitesFactory,
        \Magento\Reward\Model\Source\Customer\GroupsFactory $groupsFactory,
        array $data = []
    ) {
        $this->_websitesFactory = $websitesFactory;
        $this->_groupsFactory = $groupsFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Getter
     *
     * @return \Magento\Reward\Model\Reward\Rate
     */
    public function getRate()
    {
        return $this->_coreRegistry->registry('current_reward_rate');
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('adminhtml/*/save', ['_current' => true]),
                    'method' => 'post',
                ],
            ]
        );
        $form->setFieldNameSuffix('rate');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Reward Exchange Rate Information')]);

        if (!$this->_storeManager->isSingleStoreMode()) {
            $field = $fieldset->addField(
                'website_id',
                'select',
                [
                    'name' => 'website_id',
                    'title' => __('Website'),
                    'label' => __('Website'),
                    'values' => $this->_websitesFactory->create()->toOptionArray()
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                \Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element::class
            );
            $field->setRenderer($renderer);
        }

        $fieldset->addField(
            'customer_group_id',
            'select',
            [
                'name' => 'customer_group_id',
                'title' => __('Customer Group'),
                'label' => __('Customer Group'),
                'values' => $this->_groupsFactory->create()->toOptionArray()
            ]
        );

        $fieldset->addField(
            'direction',
            'select',
            [
                'name' => 'direction',
                'title' => __('Direction'),
                'label' => __('Direction'),
                'values' => $this->getRate()->getDirectionsOptionArray()
            ]
        );

        $rateRenderer = $this->getLayout()->createBlock(
            \Magento\Reward\Block\Adminhtml\Reward\Rate\Edit\Form\Renderer\Rate::class
        )->setRate(
            $this->getRate()
        );
        $direction = $this->getRate()->getDirection();
        if ($direction == \Magento\Reward\Model\Reward\Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY) {
            $fromIndex = 'points';
            $toIndex = 'currency_amount';
        } else {
            $fromIndex = 'currency_amount';
            $toIndex = 'points';
        }
        $fieldset->addField(
            'rate_to_currency',
            'note',
            [
                'title' => __('Rate'),
                'label' => __('Rate'),
                'value_index' => $fromIndex,
                'equal_value_index' => $toIndex
            ]
        )->setRenderer(
            $rateRenderer
        );

        $form->setUseContainer(true);
        $form->setValues($this->getRate()->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
