<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Observer;

use Magento\Catalog\Model\Category;
use Magento\CatalogPermissions\Helper\Data;
use Magento\Framework\Data\Tree\Node;

class ApplyPermissionsOnCategory
{
    /**
     * Catalog permission helper
     *
     * @var Data
     */
    protected $_catalogPermData;

    /**
     * Constructor
     *
     * @param Data $catalogPermData
     */
    public function __construct(
        Data $catalogPermData
    ) {
        $this->_catalogPermData = $catalogPermData;
    }

    /**
     * Apply category related permissions on category
     *
     * @param Node|Category $category
     * @return $this
     */
    public function execute($category)
    {
        if ($category->getData('permissions/grant_catalog_category_view') == -2
            || $category->getData('permissions/grant_catalog_category_view') != -1
            && !$this->_catalogPermData->isAllowedCategoryView()
        ) {
            $category->setIsActive(0);
            $category->setIsHidden(true);
        }

        return $this;
    }
}
