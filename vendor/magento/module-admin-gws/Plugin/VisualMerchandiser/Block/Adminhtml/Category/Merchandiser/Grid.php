<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\VisualMerchandiser\Block\Adminhtml\Category\Merchandiser;

use Magento\AdminGws\Model\Role as AdminRole;
use Magento\VisualMerchandiser\Block\Adminhtml\Category\Merchandiser\Grid as CategoryProductsGrid;

/**
 * Category products grid plugin.
 */
class Grid
{
    /**
     * @var AdminRole $adminRole
     */
    private $adminRole;

    /**
     * @param AdminRole $adminRole
     */
    public function __construct(AdminRole $adminRole)
    {
        $this->adminRole = $adminRole;
    }

    /**
     * Check admin role permissions to change columns in the grid.
     *
     * @param CategoryProductsGrid $subject
     * @param void $result
     * @param string $columnId
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAddColumn(CategoryProductsGrid $subject, $result, $columnId): void
    {
        if (! $this->adminRole->getIsAll()) {
            $this->restrictCategoryProductsEdit($subject, $columnId);
        }
    }

    /**
     * Restrict to edit products in products grid.
     *
     * @param CategoryProductsGrid $categoryProductsGrid
     * @param string $columnId
     * @return void
     */
    private function restrictCategoryProductsEdit(CategoryProductsGrid $categoryProductsGrid, string $columnId): void
    {
        if ($columnId === 'action') {
            $categoryProductsGrid->removeColumn('action');
        }

        if ($columnId === 'draggable-position') {
            $categoryProductsGrid->removeColumn('draggable-position');
        }

        if ($columnId === 'position') {
            $positionColumn = $categoryProductsGrid->getColumn('position');
            $positionColumn->setAttribute('editable', false);
        }
    }
}
