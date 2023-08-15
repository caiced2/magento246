<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product;

use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Attribute as AttributeCondition;
use Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface;

class Attribute implements FilterTextGeneratorInterface
{
    /**
     * @var string
     */
    protected $attributeCode;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->attributeCode = $data['attribute'];
    }

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
                if (!$product->hasData($this->attributeCode)) {
                    $product->load($product->getId());
                }
                $value = $product->getData($this->attributeCode);
                if (is_scalar($value)) {
                    $text = AttributeCondition::FILTER_TEXT_PREFIX . $this->attributeCode . ':' . $value;
                    if (!in_array($text, $filterText)) {
                        $filterText[] = $text;
                    }
                }
            }
        }
        return $filterText;
    }
}
