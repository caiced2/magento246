<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Rules\Rule;

class Source extends \Magento\VisualMerchandiser\Model\Rules\Rule
{
    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function applyToCollection($collection)
    {
        $options = $this->toMappedOptions(
            $this->_attribute->getSource()->getAllOptions(false, true)
        );

        $selectedValue = strtolower($this->_rule['value']);
        $ruleOperator = $this->_rule['operator'];
        $ruleValue = $selectedValue;

        if ('like' === $this->_rule['operator']) {
            $matchedOptions = preg_grep(
                '#' . preg_quote($selectedValue, '#') . '#',
                array_keys($options)
            );

            if ($matchedOptions) {
                $ruleOperator = 'in';
                $ruleValue = array_values(
                    array_intersect_key(
                        $options,
                        array_flip($matchedOptions)
                    )
                );
            }
        } elseif (isset($options[$selectedValue])) {
            $ruleValue = $options[$selectedValue];
        }

        $collection->addAttributeToFilter($this->_rule['attribute'], [
            $ruleOperator => $ruleValue
        ]);
    }
}
