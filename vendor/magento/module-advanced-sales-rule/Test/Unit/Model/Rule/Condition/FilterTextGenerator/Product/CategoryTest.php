<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\FilterTextGenerator\Product;

use Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Category;

class CategoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * test: generateFilterText()
     *
     * situation: if param is not an Address from a Quote, the generated filter text should be empty
     */
    public function testForEmptyGenerateFilterText()
    {
        $filterTextGenerator = new Category();
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
        $categories = [
            [111, 222, 333],
            [444],
            [333, 555],
        ];

        $filterTextGenerator = new Category();

        /** @var \Magento\Quote\Model\Quote\Address|\PHPUnit\Framework\MockObject\MockObject $quoteAddress */
        $quoteAddress = $this->buildQuoteAddress($categories);

        $filterText = $filterTextGenerator->generateFilterText($quoteAddress);
        $this->verifyResults($filterText, $categories);
    }

    // --- helpers ------------------------------------------------------------

    protected function verifyResults(array $filterText, array $categories)
    {
        // gather all the unique categories
        $uniqueCategories = [];
        foreach ($categories as $productCategories) {
            foreach ($productCategories as $category) {
                if (!in_array($category, $uniqueCategories)) {
                    $uniqueCategories[] = $category;
                }
            }
        }

        // verify all the category combinations are present
        $missingCats = [];
        foreach ($uniqueCategories as $category) {
            $token = ':' . $category;
            if (!$this->findMe($token, $filterText)) {
                $missingCats[] = $category;
            }
        }
        if (sizeof($missingCats)) {
            $this->fail("'filterText' is missing the following categories: " . print_r($missingCats, true));
        }

        // verify same size of the unique categories array and the results array
        $this->assertEquals(
            sizeof($uniqueCategories),
            sizeof($filterText),
            "Expected size of 'uniqueCategories' to be the same as 'filterText'"
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

    protected function buildQuoteAddress(array $categories)
    {
        $items = [];
        foreach ($categories as $productCategories) {
            $items[] = $this->buildItem($productCategories);
        }

        $className = \Magento\Quote\Model\Quote\Address::class;
        $quoteAddress = $this->createPartialMock($className, ['getAllItems']);
        $quoteAddress->expects($this->once())->method('getAllItems')->willReturn($items);

        return $quoteAddress;
    }

    protected function buildItem(array $inCategories)
    {
        $className = \Magento\Catalog\Model\Product::class;
        $product = $this->createPartialMock($className, ['getAvailableInCategories']);
        $product->expects($this->any())->method('getAvailableInCategories')->willReturn($inCategories);

        $className = \Magento\Quote\Model\Quote\Item\AbstractItem::class;
        $item = $this->getMockForAbstractClass($className, [], '', false, false, true, ['getProduct']);
        $item->expects($this->once())->method('getProduct')->willReturn($product);

        return $item;
    }
}
