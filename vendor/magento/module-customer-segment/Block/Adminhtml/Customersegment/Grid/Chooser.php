<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Block\Adminhtml\Customersegment\Grid;

use Magento\Framework\Exception\LocalizedException;
use Magento\CustomerSegment\Block\Adminhtml\Customersegment\Grid;

/**
 * Customer Segment grid
 *
 * @api
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class Chooser extends Grid
{
    private const IN_SEGMENTS_COLUMN_ID = 'in_segments';

    /**
     * Intialize grid
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        if ($this->getRequest()->getParam('current_grid_id')) {
            $this->setId($this->getRequest()->getParam('current_grid_id'));
        } else {
            $this->setId('customersegment_grid_chooser_' . $this->getId());
        }

        $this->setDefaultSort('name');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);

        $form = $this->getRequest()->getParam('form');
        if ($form) {
            $this->setRowClickCallback("{$form}.chooserGridRowClick.bind({$form})");
            $this->setCheckboxCheckCallback("{$form}.chooserGridCheckboxCheck.bind({$form})");
            $this->setRowInitCallback("{$form}.chooserGridRowInit.bind({$form})");
        }
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
    }

    /**
     * Row click javascript callback getter
     *
     * @return string
     */
    public function getRowClickCallback()
    {
        return $this->_getData('row_click_callback');
    }

    /**
     * Prepare columns for grid
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            self::IN_SEGMENTS_COLUMN_ID,
            [
                'header_css_class' => 'a-center',
                'type' => 'checkbox',
                'name' => 'in_segments',
                'values' => $this->_getSelectedSegments(),
                'align' => 'center',
                'index' => 'segment_id',
                'use_index' => true
            ]
        );
        return parent::_prepareColumns();
    }

    /**
     * Get Selected ids param from request
     *
     * @return array
     */
    protected function _getSelectedSegments()
    {
        return array_map('intval', $this->getRequest()->getPost('selected', []));
    }

    /**
     * Process column filtration values for the added column
     *
     * @param mixed $data
     * @return $this
     * @throws LocalizedException
     */
    protected function _setFilterValues($data): Chooser
    {
        parent::_setFilterValues($data);
        if (array_key_exists(self::IN_SEGMENTS_COLUMN_ID, $data)) {
            $selectedSegments = $this->_getSelectedSegments();
            $condition = [];
            if ((int)$data[self::IN_SEGMENTS_COLUMN_ID] === 1) {
                if (!empty($selectedSegments)) {
                    $condition = ['in' => $selectedSegments];
                } else {
                    $condition = new \Zend_Db_Expr('0');
                }
            } else {
                if (!empty($selectedSegments)) {
                    $condition = ['nin' => $selectedSegments];
                }
            }
            if (!empty($condition)) {
                $this->getCollection()->addFieldToFilter('segment_id', $condition);
            }
        }
        return $this;
    }

    /**
     * Grid URL getter for ajax mode
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('customersegment/index/chooserGrid', ['_current' => true]);
    }
}
