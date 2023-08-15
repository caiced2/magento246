<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab;

/**
 * Items Tab in Edit RMA form
 *
 * @api
 * @author     Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Items extends \Magento\Backend\Block\Widget\Form\Generic implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Rma eav
     *
     * @var \Magento\Rma\Helper\Eav
     */
    protected $_rmaEav;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Rma item form
     *
     * @var \Magento\Rma\Model\Item\FormFactory
     */
    protected $_itemFormFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Rma\Helper\Eav $rmaEav
     * @param \Magento\Rma\Model\Item\FormFactory $itemFormFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Rma\Helper\Eav $rmaEav,
        \Magento\Rma\Model\Item\FormFactory $itemFormFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_rmaEav = $rmaEav;
        $this->_itemFormFactory = $itemFormFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Class constructor
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('rma_items_grid');
    }

    /**
     * Get "Add Products" button
     *
     * @return string
     */
    public function getAddButtonHtml()
    {
        $addButtonData = [
            'label' => $this->escapeHtmlAttr(__('Add Products')),
            'onclick' => "rma.addProduct()",
            'class' => 'action-secondary action-add'
        ];
        return $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            $addButtonData
        )->toHtml();
    }

    /**
     * Get "Add products to RMA" button
     *
     * @return string
     */
    public function getAddProductButtonHtml()
    {
        $addButtonData = [
            'label' => $this->escapeHtmlAttr(__('Add Selected Product(s) to returns')),
            'onclick' => "rma.addSelectedProduct()",
            'class' => 'action-secondary action-add',
        ];
        return $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            $addButtonData
        )->toHtml();
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $htmlIdPrefix = 'rma_properties_';
        $form->setHtmlIdPrefix($htmlIdPrefix);

        $model = $this->_coreRegistry->registry('current_rma');

        $fieldset = $form->addFieldset('rma_item_fields', []);

        $fieldset->addField(
            'product_name',
            'text',
            ['label' => __('Product Name'), 'name' => 'product_name', 'required' => false]
        );

        $fieldset->addField(
            'product_sku',
            'text',
            ['label' => __('SKU'), 'name' => 'product_sku', 'required' => false]
        );

        //Renderer puts available quantity instead of order_item_id
        $fieldset->addField(
            'qty_ordered',
            'text',
            ['label' => __('Remaining Qty'), 'name' => 'qty_ordered', 'required' => false]
        );

        $fieldset->addField(
            'qty_requested',
            'text',
            [
                'label' => __('Requested Qty'),
                'name' => 'qty_requested',
                'required' => false,
                'class' => 'validate-greater-than-zero'
            ]
        );

        /** @var $itemForm \Magento\Rma\Model\Item\Form */
        $itemForm = $this->_itemFormFactory->create();
        $reasonOtherAttribute = $itemForm->setFormCode('default')->getAttribute('reason_other');

        $fieldset->addField(
            'reason_other',
            'text',
            [
                'label' => $reasonOtherAttribute->getStoreLabel(),
                'name' => 'reason_other',
                'maxlength' => 255,
                'required' => false
            ]
        );

        $eavHelper = $this->_rmaEav;
        $fieldset->addField(
            'reason',
            'select',
            [
                'label' => __('Reason to Return'),
                'options' => [
                    '' => '',
                ] + $eavHelper->getAttributeOptionValues(
                    'reason'
                ) + [
                    'other' => $reasonOtherAttribute->getStoreLabel(),
                ],
                'name' => 'reason',
                'required' => false
            ]
        )->setRenderer(
            $this->getLayout()->createBlock(\Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Renderer\Reason::class)
        );

        $fieldset->addField(
            'condition',
            'select',
            [
                'label' => __('Item Condition'),
                'options' => ['' => ''] + $eavHelper->getAttributeOptionValues('condition'),
                'name' => 'condition',
                'required' => false,
                'class' => 'admin__control-select'
            ]
        );

        $fieldset->addField(
            'resolution',
            'select',
            [
                'label' => __('Resolution'),
                'options' => ['' => ''] + $eavHelper->getAttributeOptionValues('resolution'),
                'name' => 'resolution',
                'required' => false,
                'class' => 'admin__control-select'
            ]
        );

        $fieldset->addField(
            'delete_link',
            'label',
            ['label' => __('Delete'), 'name' => 'delete_link', 'required' => false]
        );

        $fieldset->addField(
            'add_details_link',
            'label',
            ['label' => __('Add Details'), 'name' => 'add_details_link', 'required' => false]
        );

        $this->setForm($form);

        return $this;
    }

    /**
     * Get Header Text for Order Selection
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Items');
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Return Items');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Returns status flag about this tab can be showen or not
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}
