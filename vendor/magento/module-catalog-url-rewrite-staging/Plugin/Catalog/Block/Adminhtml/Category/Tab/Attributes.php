<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogUrlRewriteStaging\Plugin\Catalog\Block\Adminhtml\Category\Tab;

use \Magento\Staging\Model\VersionManager;

/**
 * Class Attributes
 * @TODO  should be refactored/removed in MAGETWO-43102
 */
class Attributes extends \Magento\CatalogUrlRewrite\Plugin\Catalog\Block\Adminhtml\Category\Tab\Attributes
{
    /**
     * @param \Magento\Catalog\Model\Category\DataProvider $subject
     * @param array $result
     *
     * @return array
     */
    public function afterGetAttributesMeta(
        \Magento\Catalog\Model\Category\DataProvider $subject,
        $result
    ) {
        return parent::afterGetAttributesMeta($subject, $result);
    }
}
