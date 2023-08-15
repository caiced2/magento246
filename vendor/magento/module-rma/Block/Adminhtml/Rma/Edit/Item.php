<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit;

use Magento\Rma\Model\Item as ModelItem;

/**
 * User-attributes block for RMA Item  in Admin RMA edit
 *
 * @api
 * @method int getHtmlPrefixId
 * @method \Magento\Rma\Block\Adminhtml\Rma\Edit\Item setHtmlPrefixId
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Item extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Rma data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaData;

    /**
     * Rma item form model
     *
     * @var \Magento\Rma\Model\Item\FormFactory
     */
    protected $_itemFormFactory;

    /**
     * Sales order item model
     *
     * @var \Magento\Sales\Model\Order\ItemFactory
     */
    protected $_itemFactory;

    /**
     * Define if the form has user-defined attributes
     *
     * @var bool
     */
    protected $hasNewAttributes = false;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Rma\Model\Item\FormFactory $itemFormFactory
     * @param \Magento\Sales\Model\Order\ItemFactory $itemFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Rma\Model\Item\FormFactory $itemFormFactory,
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        array $data = []
    ) {
        $this->_rmaData = $rmaData;
        $this->_itemFormFactory = $itemFormFactory;
        $this->_itemFactory = $itemFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Preparing form - container, which contains all attributes
     *
     * @return $this
     */
    public function initForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix($this->getHtmlPrefixId() . '_rma');
        $form->setFieldNameSuffix();

        $item = $this->_coreRegistry->registry('current_rma_item');

        if (!$item->getId()) {
            // for creating RMA process when we have no item loaded, $item is just empty model
            $this->_populateItemWithProductData($item);
        }

        /* @var $customerForm \Magento\Rma\Model\Item\Form */
        $customerForm = $this->_itemFormFactory->create();
        $customerForm->setEntity($item)->setFormCode('default')->initDefaultValues();

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('RMA Item Details')]);

        $fieldset->setProductName($this->escapeHtml($item->getProductAdminName()));
        $okButton = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            ['label' => __('OK'), 'class' => 'ok_button']
        );
        $fieldset->setOkButton($okButton->toHtml());

        $cancelButton = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            ['label' => __('Cancel'), 'class' => 'cancel_button']
        );
        $fieldset->setCancelButton($cancelButton->toHtml());

        $attributes = $customerForm->getUserAttributes();
        if (count($attributes) > 0) {
            $this->hasNewAttributes = true;
        }
        foreach ($attributes as $attribute) {
            $attribute->unsIsVisible();
        }
        $this->_setFieldset($attributes, $fieldset);

        $form->setValues($item->getData());
        $this->setForm($form);
        return $this;
    }

    /**
     * Preparing global layout
     *
     * You can redefine this method in child classes for changin layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        \Magento\Framework\Data\Form::setElementRenderer(
            $this->getLayout()->createBlock(
                \Magento\Backend\Block\Widget\Form\Renderer\Element::class,
                $this->getNameInLayout() . '_element'
            )
        );
        \Magento\Framework\Data\Form::setFieldsetRenderer(
            $this->getLayout()->createBlock(
                \Magento\Rma\Block\Adminhtml\Rma\Edit\Item\Renderer\Fieldset::class,
                $this->getNameInLayout() . '_fieldset'
            )
        );
        \Magento\Framework\Data\Form::setFieldsetElementRenderer(
            $this->getLayout()->createBlock(
                \Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element::class,
                $this->getNameInLayout() . '_fieldset_element'
            )
        );

        return $this;
    }

    /**
     * Return predefined additional element types
     *
     * @return array
     */
    protected function _getAdditionalElementTypes()
    {
        return [
            'text' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Item\Form\Element\Text::class,
            'textarea' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Item\Form\Element\Textarea::class,
            'image' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Item\Form\Element\Image::class,
            'boolean' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Item\Form\Element\Boolean::class,
        ];
    }

    /**
     * Add needed data (Product name) to RMA item during create process
     *
     * @param ModelItem $item
     * @return void
     */
    protected function _populateItemWithProductData($item)
    {
        if ($this->getProductId()) {
            /** @var $orderItem \Magento\Sales\Model\Order\Item */
            $orderItem = $this->_itemFactory->create()->load($this->getProductId());
            if ($orderItem && $orderItem->getId()) {
                $item->setProductAdminName($this->_rmaData->getAdminProductName($orderItem));
            }
        }
    }

    /**
     */
    public function hasNewAttributes()
    {
        return $this->hasNewAttributes;
    }
}
