<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Model\ResourceModel\Rule;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Zend_Db;

/**
 * Add website ids to sales rules collection
 */
class AddWebsiteIdsToCollection
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $ruleIdField;

    /**
     * @var string
     */
    private $websiteIdField;

    /**
     * @param DataObject $associatedEntityMap
     */
    public function __construct(
        DataObject $associatedEntityMap
    ) {
        if (!isset($associatedEntityMap['website'])
            || !array_key_exists('associations_table', $associatedEntityMap['website'])
            || !array_key_exists('rule_id_field', $associatedEntityMap['website'])
            || !array_key_exists('entity_id_field', $associatedEntityMap['website'])
        ) {
            throw new \InvalidArgumentException(
                'Invalid associated entities configuration'
            );
        }
        $this->table = $associatedEntityMap['website']['associations_table'];
        $this->ruleIdField = $associatedEntityMap['website']['rule_id_field'];
        $this->websiteIdField = $associatedEntityMap['website']['entity_id_field'];
    }

    /**
     * Eager load sales rules website ids and set them in related collection item
     *
     * @param AbstractDb $collection
     */
    public function execute(AbstractDb $collection): void
    {
        $entityIds = $collection->getColumnValues($this->ruleIdField);

        if ($entityIds) {
            $select = $collection->getConnection()
                ->select()
                ->from(
                    $collection->getResource()->getTable($this->table)
                )->where(
                    $this->ruleIdField . ' IN (?)',
                    $entityIds,
                    Zend_Db::INT_TYPE
                );

            foreach ($collection->getConnection()->fetchAll($select) as $link) {
                $item = $collection->getItemByColumnValue($this->ruleIdField, $link[$this->ruleIdField]);
                $value = $item->getData('website_ids') ?? [];
                $value[] = $link[$this->websiteIdField];
                $item->setData('website_ids', $value);
            }
        }
    }
}
