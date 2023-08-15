<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Rules\Rule;

class Boolean extends \Magento\VisualMerchandiser\Model\Rules\Rule
{
    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return void
     * @throws \Exception
     */
    public function applyToCollection($collection)
    {
        $options = [
            'YES' => 1,
            'NO' => 0
        ];

        $value = strtoupper($this->_rule['value']);
        if (array_key_exists($value, $options)) {
            $collection->addAttributeToFilter($this->_rule['attribute'], [
                $this->_rule['operator'] => $value
            ]);
        } else {
            throw new \Exception(__("Error in yes/no format"));
        }
    }
}
