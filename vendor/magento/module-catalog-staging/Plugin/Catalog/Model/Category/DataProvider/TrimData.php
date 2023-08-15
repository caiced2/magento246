<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Plugin\Catalog\Model\Category\DataProvider;

class TrimData
{
    /**
     * @param \Magento\Catalog\Model\Category\DataProvider $subject
     * @param array $data
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetData(\Magento\Catalog\Model\Category\DataProvider $subject, array $data)
    {
        foreach ($data as &$categoryData) {
            unset($categoryData['updated_in']);
            unset($categoryData['created_in']);
        }
        return $data;
    }
}
