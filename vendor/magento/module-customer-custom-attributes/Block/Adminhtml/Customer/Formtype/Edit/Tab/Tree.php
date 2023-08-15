<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Block\Adminhtml\Customer\Formtype\Edit\Tab;

use Magento\Store\Model\Store;

/**
 * Form Type Edit General Tab Block
 *
 * @api
 * @since 100.0.2
 */
class Tree extends \Magento\Backend\Block\Widget\Form implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Form\Fieldset\CollectionFactory
     */
    protected $_fieldsetFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Form\Element\CollectionFactory
     */
    protected $_elementsFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Eav\Model\ResourceModel\Form\Fieldset\CollectionFactory $fieldsetFactory
     * @param \Magento\Eav\Model\ResourceModel\Form\Element\CollectionFactory $elementsFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Registry $registry,
        \Magento\Eav\Model\ResourceModel\Form\Fieldset\CollectionFactory $fieldsetFactory,
        \Magento\Eav\Model\ResourceModel\Form\Element\CollectionFactory $elementsFactory,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_coreRegistry = $registry;
        $this->_fieldsetFactory = $fieldsetFactory;
        $this->_elementsFactory = $elementsFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current form type instance
     *
     * @return \Magento\Eav\Model\Form\Type
     */
    protected function _getFormType()
    {
        return $this->_coreRegistry->registry('current_form_type');
    }

    /**
     * Get Tree Buttons Html
     *
     * @return string
     */
    public function getTreeButtonsHtml()
    {
        $addButtonData = [
            'id' => 'add_node_button',
            'label' => $this->escapeHtmlAttr(__('New Fieldset')),
            'onclick' => 'formType.newFieldset()',
            'class' => 'add',
        ];
        return $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            $addButtonData
        )->toHtml();
    }

    /**
     * Get Fieldset Buttons Html
     *
     * @return string
     */
    public function getFieldsetButtonsHtml()
    {
        $buttons = [];
        $buttons[] = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id' => 'save_node_button',
                'label' => __('Save'),
                'onclick' => 'formType.saveFieldset()',
                'class' => 'save',
            ]
        )->toHtml();
        $buttons[] = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id' => 'delete_node_button',
                'label' => __('Remove'),
                'onclick' => 'formType.deleteFieldset()',
                'class' => 'delete',
            ]
        )->toHtml();
        $buttons[] = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id' => 'cancel_node_button',
                'label' => __('Cancel'),
                'onclick' => 'formType.cancelFieldset()',
                'class' => 'cancel',
            ]
        )->toHtml();

        return join(' ', $buttons);
    }

    /**
     * Retrieve all store objects
     *
     * @return Store[]
     */
    public function getStores()
    {
        if (!$this->hasData('stores')) {
            $this->setData('stores', $this->_storeManager->getStores(false));
        }
        return $this->_getData('stores');
    }

    /**
     * Retrieve stores array in JSON format
     *
     * @return string
     */
    public function getStoresJson()
    {
        $result = [];
        $stores = $this->getStores();
        foreach ($stores as $stores) {
            $result[$stores->getId()] = $stores->getName();
        }

        return $this->_jsonEncoder->encode($result);
    }

    /**
     * Retrieve form attributes JSON
     *
     * @return string
     */
    public function getAttributesJson()
    {
        $nodes = [];
        /** @var $fieldsetCollection \Magento\Eav\Model\ResourceModel\Form\Fieldset\Collection */
        $fieldsetCollection = $this->_fieldsetFactory->create();
        $fieldsetCollection->addTypeFilter($this->_getFormType())->setSortOrder();

        /** @var $elementCollection \Magento\Eav\Model\ResourceModel\Form\Element\Collection */
        $elementCollection = $this->_elementsFactory->create();
        $elementCollection = $elementCollection->addTypeFilter($this->_getFormType())->setSortOrder();

        foreach ($fieldsetCollection as $fieldset) {
            /* @var $fieldset \Magento\Eav\Model\Form\Fieldset */
            $node = [
                'node_id' => $fieldset->getId(),
                'parent' => null,
                'type' => 'fieldset',
                'code' => $fieldset->getCode(),
                'label' => $fieldset->getLabel(),
            ];

            foreach ($fieldset->getLabels() as $storeId => $label) {
                $node['label_' . $storeId] = $label;
            }

            $nodes[] = $node;
        }

        foreach ($elementCollection as $element) {
            /* @var $element \Magento\Eav\Model\Form\Element */
            $nodes[] = [
                'node_id' => 'a_' . $element->getId(),
                'parent' => $element->getFieldsetId(),
                'type' => 'element',
                'code' => $element->getAttribute()->getAttributeCode(),
                'label' => $element->getAttribute()->getFrontend()->getLabel(),
            ];
        }

        return $this->_jsonEncoder->encode($nodes);
    }

    /**
     * Retrieve Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Attributes');
    }

    /**
     * Retrieve Tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Attributes');
    }

    /**
     * Check is can show tab
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Check tab is hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}
