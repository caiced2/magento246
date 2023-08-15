<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product;

use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Categories;
use Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface;

class Category implements FilterTextGeneratorInterface
{
    /**
     * @param \Magento\Framework\DataObject $quoteAddress
     * @return string[]
     */
    public function generateFilterText(\Magento\Framework\DataObject $quoteAddress)
    {
        $filterText = [];
        if ($quoteAddress instanceof \Magento\Quote\Model\Quote\Address) {
            $items = $quoteAddress->getAllItems();
            foreach ($items as $item) {
                $product = $item->getProduct();
                $categoryIds = $product->getAvailableInCategories();
                foreach ($categoryIds as $categoryId) {
                    $text = Categories::FILTER_TEXT_PREFIX . $categoryId;
                    if (!in_array($text, $filterText)) {
                        $filterText[] = $text;
                    }
                }
            }
        }
        return $filterText;
    }
}
