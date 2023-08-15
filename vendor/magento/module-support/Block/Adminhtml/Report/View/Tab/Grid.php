<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Block\Adminhtml\Report\View\Tab;

/**
 * Grid widget
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Data\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Data\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Set defaults
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);
    }

    /**
     * Prevent appending grid JS so it will not be loaded (it is not needed)
     *
     * @return bool
     */
    public function canDisplayContainer()
    {
        return false;
    }

    /**
     * Return row url for js event handlers
     *
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $item
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getRowUrl($item)
    {
        return '';
    }

    /**
     * Instantiate and prepare collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var \Magento\Framework\Data\Collection $collection */
        $collection = $this->collectionFactory->create();

        $gridData = $this->getGridData();
        if (empty($gridData) || !is_array($gridData) || empty($gridData['headers'])
            || empty($gridData['data'])
        ) {
            $this->setCollection($collection);
            return $this;
        }

        foreach ($gridData['data'] as $dataValues) {
            $itemObject = new \Magento\Framework\DataObject();
            foreach ($dataValues as $valueIndex => $value) {
                $itemObject->setData($this->getColumnId($gridData['headers'][$valueIndex]), $value);
            }
            $collection->addItem($itemObject);
        }
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Define grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $gridData = $this->getGridData();
        if (empty($gridData) || !is_array($gridData) || empty($gridData['headers'])
            || empty($gridData['data'])
        ) {
            parent::_prepareColumns();
            return $this;
        }

        foreach ($gridData['headers'] as $columnLabel) {
            $this->addColumn(
                $this->getColumnId($columnLabel),
                [
                    'header' => __($columnLabel),
                    'align' => 'left',
                    'index' => $this->getColumnId($columnLabel),
                    'sortable' => false,
                    'renderer' => \Magento\Support\Block\Adminhtml\Report\View\Tab\Grid\Column\Renderer\Text::class,
                    'filter' => false
                ]
            );
        }

        return parent::_prepareColumns();
    }

    /**
     * Get column id text by column name
     *
     * @param string $column
     * @return string
     */
    protected function getColumnId($column)
    {
        $column = preg_replace('/[^\p{L}\p{N}\s]/u', '', $column);
        $column = strtolower(str_replace(' ', '_', $column));
        if ($column == 'id') {
            $column .= 'value';
        }
        return $column;
    }
}
