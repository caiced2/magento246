<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Plugin;

use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Model\Category;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Framework\Registry;

/**
 * Class UpdateIdentitiesPlugin to update identifiers for produced content with current category
 */
class UpdateIdentitiesPlugin
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var ConfigInterface
     */
    private $permissionsConfig;

    /**
     * @param Registry $coreRegistry
     * @param ConfigInterface $permissionsConfig
     */
    public function __construct(
        Registry $coreRegistry,
        ConfigInterface $permissionsConfig
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->permissionsConfig = $permissionsConfig;
    }

    /**
     * Update identifiers for produced content with current category
     *
     * @param View $subject
     * @param array $result
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetIdentities(
        View $subject,
        array $result
    ) {
        if (!$this->permissionsConfig->isEnabled()) {
            return $result;
        }

        $category = $this->coreRegistry->registry('current_category');
        if ($category) {
            $result[] = Category::CACHE_TAG . '_' . $category->getId();
        }
        return $result;
    }
}
