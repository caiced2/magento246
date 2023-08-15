<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\ResourceModel\Rma\Grid;

/**
 * RMA grid collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Collection extends \Magento\Rma\Model\ResourceModel\Rma\Collection
{
    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'rma_rma_grid_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'rma_grid_collection';

    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setMainTable('magento_rma_grid');
    }

    /**
     * Get SQL for get record count
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $unionSelect = clone $this->getSelect();

        $unionSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $unionSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $unionSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);

        $countSelect = clone $this->getSelect();
        $countSelect->reset();
        $countSelect->from(['a' => $unionSelect], 'COUNT(*)');

        return $countSelect;
    }

    /**
     * Emulate simple add attribute filter to collection
     *
     * @param string $attribute
     * @param mixed $condition
     * @return \Magento\Rma\Model\ResourceModel\Rma\Grid\Collection
     */
    public function addAttributeToFilter($attribute, $condition = null)
    {
        if (!is_string($attribute) || $condition === null) {
            return $this;
        }

        return $this->addFieldToFilter($attribute, $condition);
    }
}
