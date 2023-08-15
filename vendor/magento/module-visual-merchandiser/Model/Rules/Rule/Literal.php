<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Rules\Rule;

class Literal extends \Magento\VisualMerchandiser\Model\Rules\Rule
{
    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return void
     */
    public function applyToCollection($collection)
    {
        if ($this->_rule['operator'] == 'like') {
            $ruleValue = '%' . $this->_rule['value'] . '%';
        } else {
            $ruleValue = $this->_rule['value'];
        }
        $collection->addAttributeToFilter($this->_rule['attribute'], [
            $this->_rule['operator'] => $ruleValue
        ]);
    }
}
