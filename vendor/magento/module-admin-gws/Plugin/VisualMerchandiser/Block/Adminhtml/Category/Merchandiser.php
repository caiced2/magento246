<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\VisualMerchandiser\Block\Adminhtml\Category;

use Magento\AdminGws\Model\Role as AdminRole;
use Magento\Backend\Block\Widget\Button as WidgetButton;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\VisualMerchandiser\Block\Adminhtml\Category\Merchandiser as CategoryProductsBlock;

/**
 * Category products block plugin.
 */
class Merchandiser
{
    /**
     * @var AdminRole $adminRole
     */
    private $adminRole;

    /**
     * @var CategoryRepositoryInterface $categoryRepository
     */
    private $categoryRepository;

    /**
     * @param AdminRole $adminRole
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        AdminRole $adminRole,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->adminRole = $adminRole;
    }

    /**
     * Check admin role permissions to change some view elements in the block.
     *
     * @param CategoryProductsBlock $subject
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeToHtml(CategoryProductsBlock $subject): void
    {
        if (!$this->adminRole->getIsAll()) {
            $categoryId = (int) $subject->getCategoryId();
            $allowedCategoriesIds = array_keys($this->adminRole->getAllowedRootCategories());
            if ($categoryId > 0 && !in_array($categoryId, $allowedCategoriesIds)) {
                $category = $this->categoryRepository->get($categoryId);
                $parentIds = $category->getParentIds();
                if (empty(array_intersect($parentIds, $allowedCategoriesIds))) {
                    $this->restrictCategoryProductsAdd($subject);
                }
            }
        }
    }

    /**
     * Disable add product button.
     *
     * @param CategoryProductsBlock $categoryProductsBlock
     * @return void
     */
    public function restrictCategoryProductsAdd(CategoryProductsBlock $categoryProductsBlock): void
    {
        /** @var WidgetButton|null $addProductsButton */
        $addProductsButton = $categoryProductsBlock->getChildBlock('add_products_button');
        if ($addProductsButton) {
            $addProductsButton->setDisabled(true);
        }
    }
}
