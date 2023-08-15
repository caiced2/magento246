<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * TargetRule Catalog Product Attributes Backend Model
 *
 */
namespace Magento\TargetRule\Model\Catalog\Product\Attribute\Backend;

class Rule extends \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
{
    /**
     * Before attribute save prepare data
     *
     * @param \Magento\Catalog\Model\Product $object
     * @return \Magento\TargetRule\Model\Catalog\Product\Attribute\Backend\Rule
     */
    public function beforeSave($object)
    {
        $attributeName = $this->getAttribute()->getName();
        $useDefault = $object->getData($attributeName . '_default');

        if ($useDefault == 1) {
            $object->setData($attributeName, null);
        }

        return $this;
    }
}
