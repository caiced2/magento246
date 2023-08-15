<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Model\Rule\Identifier;

class DataProvider extends \Magento\CatalogRule\Model\Rule\DataProvider
{
    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        /** @var \Magento\CatalogRule\Model\Rule $rule */
        foreach ($this->collection->getItems() as $rule) {
            $this->loadedData[$rule->getId()] = [
                'rule_id' => $rule->getId(),
                'title' => $rule->getName()
            ];
        }

        return $this->loadedData;
    }

    /**
     * @return array
     * Overwrite default metadata config
     */
    protected function getMetadata()
    {
        return [];
    }
}
