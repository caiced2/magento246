<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Address;

use Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface;

abstract class AbstractFilterGenerator implements FilterTextGeneratorInterface
{
    /**
     * @var string
     */
    protected $attribute;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->attribute = $data['attribute'];
    }

    /**
     * @param \Magento\Framework\DataObject $quoteAddress
     * @return string[]
     */
    public function generateFilterText(\Magento\Framework\DataObject $quoteAddress)
    {
        $filterText = [];
        if ($quoteAddress instanceof \Magento\Quote\Model\Quote\Address) {
            $value = $quoteAddress->getData($this->attribute);
            if (is_scalar($value)) {
                $filterText[] = $this->getFilterTextPrefix() . $this->attribute . ':' . $value;
            }
        }
        return $filterText;
    }

    /**
     * @return string
     */
    abstract protected function getFilterTextPrefix();
}
