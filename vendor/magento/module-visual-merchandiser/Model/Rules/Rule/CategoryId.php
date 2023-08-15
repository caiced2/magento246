<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Rules\Rule;

use Magento\Framework\Exception\NoSuchEntityException;

class CategoryId extends \Magento\VisualMerchandiser\Model\Rules\Rule
{
    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @param array $rule
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        $rule,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct($rule, $attribute);
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return void
     */
    public function applyToCollection($collection)
    {
        $categoryIds = $this->_rule['value'];
        $categoryIds = explode(',', $categoryIds);
        $categoryIds = array_map('trim', $categoryIds);
        $productsIds = [];

        foreach ($categoryIds as $categoryId) {
            try {
                $category = $this->categoryRepository->get($categoryId);
            } catch (NoSuchEntityException $e) {
                $this->notices[] = __('Category ID \'%1\' not found', $categoryId);
                continue;
            }
            $productsIds = array_merge($productsIds, array_keys($category->getProductsPosition()));
        }
        $collection->addFieldToFilter('entity_id', [
            'in' => array_unique($productsIds)
        ]);
    }

    /**
     * @return array
     */
    public static function getOperators()
    {
        return [
            'eq' => __('Equal')
        ];
    }
}
