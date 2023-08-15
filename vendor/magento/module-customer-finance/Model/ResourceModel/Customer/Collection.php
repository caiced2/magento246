<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerFinance\Model\ResourceModel\Customer;

use Magento\CustomerFinance\Model\ResourceModel\Customer\Attribute\Finance\Collection as AttributeFinanceCollection;

/**
 * Customized customers collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends \Magento\Customer\Model\ResourceModel\Customer\Collection
{
    /**
     * Additional filters to use
     *
     * @var string[]
     */
    protected $_usedFiltersNotNull = [];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Reward\Model\ResourceModel\Reward
     */
    protected $_resourceReward;

    /**
     * @var \Magento\CustomerBalance\Model\ResourceModel\Balance
     */
    protected $resourceBalance;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Eav\Model\EntityFactory $eavEntityFactory
     * @param \Magento\Eav\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Validator\UniversalFactory $universalFactory
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot
     * @param \Magento\Framework\DataObject\Copy\Config $fieldsetConfig
     * @param \Magento\Reward\Model\ResourceModel\Reward $resourceReward
     * @param \Magento\CustomerBalance\Model\ResourceModel\Balance $resourceBalance
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param mixed $connection
     * @param string $modelName
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Eav\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\DataObject\Copy\Config $fieldsetConfig,
        \Magento\Reward\Model\ResourceModel\Reward $resourceReward,
        \Magento\CustomerBalance\Model\ResourceModel\Balance $resourceBalance,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        $modelName = self::CUSTOMER_MODEL_NAME
    ) {
        $this->_resourceReward = $resourceReward;
        $this->resourceBalance = $resourceBalance;
        $this->_storeManager = $storeManager;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $entitySnapshot,
            $fieldsetConfig,
            $connection,
            $modelName
        );
    }

    /**
     * Join with reward points table
     *
     * @param \Magento\Customer\Model\ResourceModel\Attribute\Collection $customerEntityAttributeCollection
     * @param \Magento\Framework\Data\Collection $customerFinanceAttributeCollection
     * @return $this
     */
    public function joinWithRewardPoints(
        \Magento\Customer\Model\ResourceModel\Attribute\Collection $customerEntityAttributeCollection,
        \Magento\Framework\Data\Collection $customerFinanceAttributeCollection
    ) {
        /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
        $attribute = $customerFinanceAttributeCollection->getItemById(
            AttributeFinanceCollection::CUSTOMER_ENTITY_FINANCE_ATTRIBUTE_REWARD_POINTS
        );

        $joinFlag = 'join_reward_points';
        if (!$this->getFlag($joinFlag)) {
            /** @var $website \Magento\Store\Model\Website */
            foreach ($this->_storeManager->getWebsites() as $website) {
                $tableName = $this->_resourceReward->getMainTable();
                $tableAlias = $tableName . $website->getId();
                $fieldName = 'points_balance';
                $fieldAlias = $website->getCode() .
                    '_' .
                    AttributeFinanceCollection::COLUMN_REWARD_POINTS;

                $this->joinTable(
                    [$tableAlias => $tableName],
                    'customer_id = entity_id',
                    [$fieldAlias => $fieldName],
                    ['website_id' => $website->getId()],
                    'left'
                );

                $this->_usedFiltersNotNull[] = $tableAlias . '.' . $fieldName;
                $this->_addAttributeToCollection($attribute, $fieldAlias, $customerEntityAttributeCollection);
            }
            $this->setFlag($joinFlag, true);
        }

        return $this;
    }

    /**
     * Join with store credit table
     *
     * @param \Magento\Customer\Model\ResourceModel\Attribute\Collection $customerEntityAttributeCollection
     * @param \Magento\Framework\Data\Collection $customerFinanceAttributeCollection
     * @return $this
     */
    public function joinWithCustomerBalance(
        \Magento\Customer\Model\ResourceModel\Attribute\Collection $customerEntityAttributeCollection,
        \Magento\Framework\Data\Collection $customerFinanceAttributeCollection
    ) {
        /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
        $attribute = $customerFinanceAttributeCollection->getItemById(
            AttributeFinanceCollection::CUSTOMER_ENTITY_FINANCE_ATTRIBUTE_CUSTOMER_BALANCE
        );

        $joinFlag = 'join_customer_balance';
        if (!$this->getFlag($joinFlag)) {
            /** @var $website \Magento\Store\Model\Website */
            foreach ($this->_storeManager->getWebsites() as $website) {
                $tableName = $this->resourceBalance->getMainTable();
                $tableAlias = $tableName . $website->getId();
                $fieldName = 'amount';
                $fieldAlias = $website->getCode() .
                    '_' .
                    AttributeFinanceCollection::COLUMN_CUSTOMER_BALANCE;

                $this->joinTable(
                    [$tableAlias => $tableName],
                    'customer_id = entity_id',
                    [$fieldAlias => $fieldName],
                    ['website_id' => $website->getId()],
                    'left'
                );

                $this->_usedFiltersNotNull[] = $tableAlias . '.' . $fieldName;
                $this->_addAttributeToCollection($attribute, $fieldAlias, $customerEntityAttributeCollection);
            }
            $this->setFlag($joinFlag, true);
        }

        return $this;
    }

    /**
     * Additional filters
     *
     * @return $this
     */
    protected function _beforeLoad()
    {
        if ($this->_usedFiltersNotNull) {
            $filterArray = [];
            foreach ($this->_usedFiltersNotNull as $filter) {
                $filterArray[] = $this->getSelect()->getConnection()->prepareSqlCondition(
                    $filter,
                    ['notnull' => true]
                );
            }
            $conditionStr = implode(' OR ', $filterArray);
            $this->getSelect()->where($conditionStr);
        }

        return parent::_beforeLoad();
    }

    /**
     * Add attribute (cloned) to collection with new code and new unique id
     *
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param string $code
     * @param \Magento\Framework\Data\Collection $attributeCollection
     *
     * @return void
     */
    protected function _addAttributeToCollection($attribute, $code, $attributeCollection)
    {
        $maxId = 0;
        foreach ($attributeCollection->getItems() as $item) {
            /** @var $item \Magento\Eav\Model\Entity\Attribute */
            $maxId = max($maxId, $item->getId());
        }

        $addAttribute = clone $attribute;
        $addAttribute->setAttributeCode($code);
        $addAttribute->setId($maxId + 1);
        $attributeCollection->addItem($addAttribute);
    }
}
