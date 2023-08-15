<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\FilterTextGenerator\Address;

use Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Address\RegionId;

class RegionIdTest extends \PHPUnit\Framework\TestCase
{
    /**
     * test: generateFilterText()
     *
     * situation: if param is not an Address from a Quote, the generated filter text should be empty
     */
    public function testForEmptyGenerateFilterText()
    {
        $filterTextGenerator = new RegionId(['attribute' => 'kiwi']);
        $param = new \Magento\Framework\DataObject();
        $filterText = $filterTextGenerator->generateFilterText($param);
        $this->assertEmpty($filterText, "Expected 'filterText' to be empty");
    }

    /**
     * test: generateFilterText()
     *
     * situation: typical usage
     *
     * @dataProvider generateFilterTextDataProvider
     */
    public function testGenerateFilterText($attrCode, $attrValue, $expected)
    {
        $filterTextGenerator = new RegionId(['attribute' => $attrCode]);

        /** @var \Magento\Quote\Model\Quote\Address|\PHPUnit\Framework\MockObject\MockObject $quoteAddress */
        $quoteAddress = $this->buildQuoteAddress($attrCode, $attrValue);

        $filterText = $filterTextGenerator->generateFilterText($quoteAddress);
        $this->verifyResults($filterText, $expected);
    }

    /**
     * @return array
     */
    public function generateFilterTextDataProvider()
    {
        return [
            'typical' => [
                'attrCode' => 'kiwi',
                'attrValue' => 'fearless',
                'expected' => ':kiwi:fearless',
            ],

            'null value' => [
                'attrCode' => 'kiwi',
                'attrValue' => null,
                'expected' => null,
            ],

            'array value' => [
                'attrCode' => 'kiwi',
                'attrValue' => ['some', 'random', 'array'],
                'expected' => null,
            ],

            'object value' => [
                'attrCode' => 'kiwi',
                'attrValue' => new \Magento\Framework\DataObject(),
                'expected' => null,
            ],
        ];
    }

    // --- helpers ------------------------------------------------------------

    protected function verifyResults(array $filterText, $expectedValue)
    {
        if ($expectedValue === null) {
            $this->assertEmpty($filterText, "'filterText' should be empty. Actual is" . print_r($filterText, true));
            return;
        }

        if (!$this->findMe($expectedValue, $filterText)) {
            $this->fail("'filterText' does not contain expected value: " . $expectedValue);
        }

        $this->assertTrue(sizeof($filterText) == 1, "Expected 'filterText' to only have 1 entry in it");
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

    protected function buildQuoteAddress($attrCode, $attrValue)
    {
        $className = \Magento\Quote\Model\Quote\Address::class;
        $quoteAddress = $this->createPartialMock($className, ['getData']);
        $quoteAddress->expects($this->once())->method('getData')->with($attrCode)->willReturn($attrValue);

        return $quoteAddress;
    }
}
