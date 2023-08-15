<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerFinance\Model\ResourceModel\Customer\Attribute\Finance;

/**
 * Export customer finance entity model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Collection extends \Magento\Framework\Data\Collection
{
    /**#@+
     * Customer entity finance attribute ids
     */
    const CUSTOMER_ENTITY_FINANCE_ATTRIBUTE_CUSTOMER_BALANCE = 1;

    const CUSTOMER_ENTITY_FINANCE_ATTRIBUTE_REWARD_POINTS = 2;

    /**#@-*/

    /**#@+
     * Column names
     */
    const COLUMN_CUSTOMER_BALANCE = 'store_credit';

    const COLUMN_REWARD_POINTS = 'reward_points';

    /**#@-*/

    /**#@-*/
    protected $_orderField;

    /**
     * @var \Magento\Eav\Model\AttributeFactory
     */
    protected $_attributeFactory;

    /**
     * Import export data
     *
     * @var \Magento\CustomerFinance\Helper\Data
     */
    protected $_customerFinanceData = null;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Magento\CustomerFinance\Helper\Data $customerFinanceData
     * @param \Magento\Eav\Model\AttributeFactory $attributeFactory
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Magento\CustomerFinance\Helper\Data $customerFinanceData,
        \Magento\Eav\Model\AttributeFactory $attributeFactory
    ) {
        $this->_customerFinanceData = $customerFinanceData;
        $this->_attributeFactory = $attributeFactory;
        parent::__construct($entityFactory);

        if ($this->_customerFinanceData->isCustomerBalanceEnabled()) {
            $storeCreditData = [
                'attribute_id' => self::CUSTOMER_ENTITY_FINANCE_ATTRIBUTE_CUSTOMER_BALANCE,
                'attribute_code' => self::COLUMN_CUSTOMER_BALANCE,
                'frontend_label' => __('Store Credit'),
                'backend_type' => 'decimal',
                'is_required' => false,
            ];
            $this->addItem(
                $this->_attributeFactory->createAttribute(\Magento\Eav\Model\Entity\Attribute::class, $storeCreditData)
            );
        }

        if ($this->_customerFinanceData->isRewardPointsEnabled()) {
            $rewardPointsData = [
                'attribute_id' => self::CUSTOMER_ENTITY_FINANCE_ATTRIBUTE_REWARD_POINTS,
                'attribute_code' => self::COLUMN_REWARD_POINTS,
                'frontend_label' => __('Reward Points'),
                'backend_type' => 'int',
                'is_required' => false,
            ];
            $this->addItem(
                $this->_attributeFactory->createAttribute(\Magento\Eav\Model\Entity\Attribute::class, $rewardPointsData)
            );
        }
    }

    /**
     * Add select order
     *
     * @param  string $field
     * @param  string $direction
     * @return $this
     */
    public function setOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        $this->_orderField = $field;
        uasort($this->_items, [$this, 'compareAttributes']);

        if ($direction == self::SORT_ORDER_DESC) {
            $this->_items = array_reverse($this->_items, true);
        }

        return $this;
    }

    /**
     * Compare two collection items
     *
     * @param \Magento\Framework\DataObject $a
     * @param \Magento\Framework\DataObject $b
     * @return int
     */
    public function compareAttributes(\Magento\Framework\DataObject $a, \Magento\Framework\DataObject $b)
    {
        return strnatcmp($a->getData($this->_orderField), $b->getData($this->_orderField));
    }
}
