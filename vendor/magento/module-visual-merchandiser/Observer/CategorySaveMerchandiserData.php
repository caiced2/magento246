<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Observer;

use Magento\Catalog\Model\Category;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;

class CategorySaveMerchandiserData implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\VisualMerchandiser\Model\Position\Cache
     */
    protected $_cache;

    /**
     * @var \Magento\VisualMerchandiser\Model\Rules
     */
    protected $_rules;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @param \Magento\VisualMerchandiser\Model\Position\Cache $cache
     * @param \Magento\VisualMerchandiser\Model\Rules $rules
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        \Magento\VisualMerchandiser\Model\Position\Cache $cache,
        \Magento\VisualMerchandiser\Model\Rules $rules,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
    ) {
        $this->_cache = $cache;
        $this->_rules = $rules;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Category $category */
        $category = $observer->getEvent()->getCategory();
        // Assign cached positions
        $cacheKey = $observer->getEvent()->getRequest()->getPostValue(
            \Magento\VisualMerchandiser\Model\Position\Cache::POSITION_CACHE_KEY
        );
        $positions = $this->_cache->getPositions($cacheKey);
        if (is_array($positions)) {
            $category->setPostedProducts(
                $positions
            );
        }

        if (!$this->validateData($observer)) {
            return;
        }

        // Save smart category rules (or clear it)
        $postData = $observer->getEvent()->getRequest()->getPostValue();
        $rule = $this->_rules->loadByCategory($category);
        if ($rule->getId() !== null || !empty($postData['smart_category_rules'])) {
            $ruleOrigData = $rule->getOrigData();
            if ($ruleOrigData) {
                $category->setOrigData('is_smart_category', $ruleOrigData['is_active']);
                $category->setOrigData('smart_category_rules', $ruleOrigData['conditions_serialized']);
            }
            $rule->setData([
                'rule_id' => $rule->getId(),
                'category_id' => $category->getId(),
                'is_active' => $postData['is_smart_category'] == 1 ? '1' : '0',
                'conditions_serialized' => $postData['smart_category_rules'] ?? ''
            ]);
            $rule->save();
        }
    }

    /**
     * Can't save smart rules if it's a category without an ID or the smart category is not set
     *
     * @param Observer $observer
     * @return bool
     */
    private function validateData(\Magento\Framework\Event\Observer $observer): bool
    {
        /** @var Category $category */
        $category = $observer->getEvent()->getCategory();
        $postData = $observer->getEvent()->getRequest()->getPostValue();

        return !($category->isObjectNew() || !$category->getId() || empty($category->getOrigData())
            || !isset($postData['is_smart_category']));
    }
}
