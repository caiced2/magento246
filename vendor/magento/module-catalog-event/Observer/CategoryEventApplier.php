<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Observer;

class CategoryEventApplier
{
    private const FIRST_N_CATEGORIES_TO_REMOVE = 2;

    /**
     * @var array
     */
    private $categoryEventCache;

    /**
     * Apply event to category
     *
     * @param \Magento\Framework\Data\Tree\Node|\Magento\Catalog\Model\Category $category
     * @param \Magento\Framework\Data\Collection $eventCollection
     * @return $this
     */
    public function applyEventToCategory($category, \Magento\Framework\Data\Collection $eventCollection)
    {
        if (!$category->getPath()) {
            return $this;
        }
        $categoryIds = \array_reverse($this->parseCategoryPath($category->getPath()));

        if (!$categoryIds) {
            return $this;
        }

        if ($this->categoryEventCache && \array_key_exists($category->getId(), $this->categoryEventCache)) {
            if ($this->categoryEventCache[$category->getId()]) {
                $category->setEvent($this->categoryEventCache[$category->getId()]);
            }
            return $this;
        }

        foreach ($categoryIds as $categoryId) {
            // Walk through category path, search event for category
            $event = $eventCollection->getItemByColumnValue('category_id', $categoryId);
            if ($event) {
                $category->setEvent($event);
                $this->setCategoryEventCache($categoryIds, $event);

                return $this;
            }
        }
        $this->setCategoryEventCache($categoryIds, null);

        return $this;
    }

    /**
     * Cache category events
     *
     * @param array $categoryIds
     * @param \Magento\CatalogEvent\Model\Event $event
     * @return void
     */
    private function setCategoryEventCache(array $categoryIds, $event)
    {
        foreach ($categoryIds as $categoryId) {
            $this->categoryEventCache[$categoryId] = $event;
        }
    }

    /**
     * Parse categories ids from category path
     *
     * @param string $path
     * @return string[]
     */
    public function parseCategoryPath($path)
    {
        // remove category tree id and root category, e.g. [1,2,3] => 3
        return \array_slice(explode('/', $path), self::FIRST_N_CATEGORIES_TO_REMOVE);
    }
}
