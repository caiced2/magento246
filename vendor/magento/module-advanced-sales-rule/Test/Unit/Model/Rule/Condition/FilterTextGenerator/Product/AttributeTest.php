<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\FilterTextGenerator\Product;

use Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Attribute;

class AttributeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * test: generateFilterText()
     *
     * situation: if param is not an Address from a Quote, the generated filter text should be empty
     */
    public function testForEmptyGenerateFilterText()
    {
        $filterTextGenerator = new Attribute(['attribute' => 'kiwi']);
        $param = new \Magento\Framework\DataObject();
        $filterText = $filterTextGenerator->generateFilterText($param);
        $this->assertEmpty($filterText, "Expected 'filterText' to be empty");
    }

    /**
     * test: generateFilterText()
     *
     * situation: typical usage
     */
    public function testGenerateFilterText()
    {
        $attrCode = 'kiwi';
        $attrValues = ['bird', 'fruit', 'shoe polish', 'bird'];

        $filterTextGenerator = new Attribute(['attribute' => $attrCode]);

        /** @var \Magento\Quote\Model\Quote\Address|\PHPUnit\Framework\MockObject\MockObject $quoteAddress */
        $quoteAddress = $this->buildQuoteAddress($attrCode, $attrValues);

        $filterText = $filterTextGenerator->generateFilterText($quoteAddress);
        $this->verifyResults($filterText, $attrCode, $attrValues);
    }

    // --- helpers ------------------------------------------------------------

    protected function verifyResults(array $filterText, $attrCode, array $attrValues)
    {
        // gather all the unique attribute values
        $uniqueAttrValues = [];
        foreach ($attrValues as $value) {
            if (!in_array($value, $uniqueAttrValues)) {
                $uniqueAttrValues[] = $value;
            }
        }

        // verify all the attribute combinations are present
        $missingAttrs = [];
        foreach ($uniqueAttrValues as $value) {
            $token = $attrCode . ':' . $value;
            if (!$this->findMe($token, $filterText)) {
                $missingAttrs[] = $token;
            }
        }
        if (sizeof($missingAttrs)) {
            $this->fail("'filterText' is missing the following attributes: " . print_r($missingAttrs, true));
        }

        // verify same size of the unique attribute values array and the results array
        $this->assertEquals(
            sizeof($uniqueAttrValues),
            sizeof($filterText),
            "Expected size of 'uniqueAttrValues' to be the same as 'filterText'"
        );
    }

    protected function findMe($needle, array $haystack)
    {
        foreach ($haystack as $entry) {
            if (strpos($entry, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    // this will also add some additional "don't care about these" items
    protected function buildQuoteAddress($attrCode, array $attrValues)
    {
        $items = [];

        $notPresent = true;
        // build the valid items
        foreach ($attrValues as $value) {
            if ($notPresent) {
                $notPresent = false;
                $items[] = $this->buildItemNeedLoad($attrCode, $attrValues);
            }
            // build an item that has the {code:value} pair
            $items[] = $this->buildItem($attrCode, $value);
        }

        // build some "don't care about these" items that have the following values: {null, array, object}
        $items[] = $this->buildItem($attrCode, null);
        $items[] = $this->buildItem($attrCode, ['some', 'random', 'array']);
        $items[] = $this->buildItem($attrCode, new \Magento\Framework\DataObject());

        $className = \Magento\Quote\Model\Quote\Address::class;
        $quoteAddress = $this->createPartialMock($className, ['getAllItems']);
        $quoteAddress->expects($this->once())->method('getAllItems')->willReturn($items);

        return $quoteAddress;
    }

    protected function buildItemNeedLoad($attrCode, $value)
    {
        $className = \Magento\Catalog\Model\Product::class;
        $product = $this->createPartialMock($className, ['getData', 'hasData', 'load']);
        $product->expects($this->once())->method('hasData')->with($attrCode)->willReturn(false);
        $product->expects($this->once())->method('load')->willReturnSelf();
        $product->expects($this->once())->method('getData')->with($attrCode)->willReturn($value);

        $className = \Magento\Quote\Model\Quote\Item\AbstractItem::class;
        $item = $this->getMockForAbstractClass($className, [], '', false, false, true, ['getProduct']);
        $item->expects($this->once())->method('getProduct')->willReturn($product);

        return $item;
    }

    protected function buildItem($attrCode, $value)
    {
        $className = \Magento\Catalog\Model\Product::class;
        $product = $this->createPartialMock($className, ['getData', 'hasData', 'load']);
        $product->expects($this->any())->method('hasData')->with($attrCode)->willReturn($value);
        $product->expects($this->any())->method('getData')->with($attrCode)->willReturn($value);

        $className = \Magento\Quote\Model\Quote\Item\AbstractItem::class;
        $item = $this->getMockForAbstractClass($className, [], '', false, false, true, ['getProduct']);
        $item->expects($this->once())->method('getProduct')->willReturn($product);

        return $item;
    }
}
