<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Block\Adminhtml\Customer\Formtype;

/**
 * Form Types Grid Block
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Eav\Model\ResourceModel\Form\Type\CollectionFactory
     */
    protected $_formTypesFactory;

    /**
     * @var \Magento\Framework\View\Design\Theme\LabelFactory
     */
    protected $_themeLabelFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Eav\Model\ResourceModel\Form\Type\CollectionFactory $formTypesFactory
     * @param \Magento\Framework\View\Design\Theme\LabelFactory $themeLabelFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Eav\Model\ResourceModel\Form\Type\CollectionFactory $formTypesFactory,
        \Magento\Framework\View\Design\Theme\LabelFactory $themeLabelFactory,
        array $data = []
    ) {
        $this->_formTypesFactory = $formTypesFactory;
        $this->_themeLabelFactory = $themeLabelFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Initialize Grid Block
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setSaveParametersInSession(true);
        $this->setDefaultSort('code');
        $this->setDefaultDir('asc');
    }

    /**
     * Prepare grid collection object
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var $collection \Magento\Eav\Model\ResourceModel\Form\Type\Collection */
        $collection = $this->_formTypesFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare Grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('code', ['header' => __('Type Code'), 'index' => 'code']);

        $this->addColumn('label', ['header' => __('Label'), 'index' => 'label']);

        $this->addColumn('store_id', ['header' => __('Store View'), 'index' => 'store_id', 'type' => 'store']);

        /** @var $label \Magento\Framework\View\Design\Theme\Label */
        $label = $this->_themeLabelFactory->create();
        $design = $label->getLabelsCollection();
        array_unshift($design, ['value' => 'all', 'label' => __('All Themes')]);
        $this->addColumn(
            'theme',
            [
                'header' => __('Theme'),
                'type' => 'theme',
                'index' => 'theme',
                'options' => $design,
                'with_empty' => true,
                'default' => __('All Themes')
            ]
        );

        $this->addColumn(
            'is_system',
            [
                'header' => __('System'),
                'index' => 'is_system',
                'type' => 'options',
                'options' => [0 => __('No'), 1 => __('Yes')]
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Retrieve row click URL
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/*/edit', ['type_id' => $row->getId()]);
    }
}
