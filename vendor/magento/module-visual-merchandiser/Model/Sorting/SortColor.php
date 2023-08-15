<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Sorting;

use \Magento\Framework\DB\Select;

class SortColor extends SortAbstract implements SortInterface
{
    public const XML_PATH_COLOR_ORDER = 'visualmerchandiser/options/color_order';
    public const XML_PATH_COLOR_ATTR = 'visualmerchandiser/options/color_attribute_code';

    /**
     * Perform sorting.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function sort(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
    ) {
        $colorOrderConf = $this->scopeConfig->getValue(self::XML_PATH_COLOR_ORDER);

        if (strlen(trim((string)$colorOrderConf)) === 0) {
            return $collection;
        }

        $colorAttributeOrder = preg_split('/\n|\r\n?/', $colorOrderConf);
        $colorAttributeOrder = array_map('trim', $colorAttributeOrder);
        $colorAttributeOrder = array_reverse($colorAttributeOrder);

        if (count($colorAttributeOrder) == 0) {
            return $collection;
        }

        $colorAttrName = $this->scopeConfig->getValue(self::XML_PATH_COLOR_ATTR);

        $collection->joinAttribute('color', 'catalog_product/'.$colorAttrName, 'entity_id', null, 'left');

        $table = $collection->getConnection()->getTableName('eav_attribute_option_value');
        $collection->getSelect()->joinLeft(
            ['option_value' => $table],
            "`at_color`.`value` = `option_value`.`option_id`",
            ['color_value' => 'value']
        );

        $fieldList = $collection->getConnection()->quote($colorAttributeOrder);
        $collection->getSelect()
            ->reset(Select::ORDER)
            ->order(new \Zend_Db_Expr("FIELD(color_value, {$fieldList}) " . $collection::SORT_ORDER_DESC));

        return $collection;
    }

    /**
     * Returns label.
     *
     * @return string
     */
    public function getLabel()
    {
        return __("Sort by color");
    }
}
