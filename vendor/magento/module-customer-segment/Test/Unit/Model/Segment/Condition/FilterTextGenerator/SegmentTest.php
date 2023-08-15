<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\FilterTextGenerator;

use Magento\CustomerSegment\Model\Segment\Condition\FilterTextGenerator\Segment;

class SegmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Segment
     */
    protected $filterTextGenerator;

    /**
     * @var \Magento\CustomerSegment\Helper\Data|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $segmentHelper;

    /**
     * @var \Magento\CustomerSegment\Model\Customer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $segmentCustomer;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->segmentHelper = $this->getMockBuilder(\Magento\CustomerSegment\Helper\Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isEnabled'])
            ->getMock();

        $this->segmentCustomer = $this->getMockBuilder(\Magento\CustomerSegment\Model\Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCurrentCustomerSegmentIds', 'getCustomerSegmentIdsForWebsite'])
            ->getMock();

        $className = \Magento\CustomerSegment\Model\Segment\Condition\FilterTextGenerator\Segment::class;
        $this->filterTextGenerator = $this->objectManager->getObject(
            $className,
            [
                'segmentHelper' => $this->segmentHelper,
                'segmentCustomer' => $this->segmentCustomer,
            ]
        );
    }

    /**
     * test: generateFilterText()
     *
     * situation: if param is not an Address from a Quote, the generated filter text should be empty
     */
    public function testForEmptyGenerateFilterText()
    {
        $param = new \Magento\Framework\DataObject();
        $filterText = $this->filterTextGenerator->generateFilterText($param);
        $this->assertEmpty($filterText, "Expected 'filterText' to be empty when parameter is not a QuoteAddress");
    }

    /**
     * test: generateFilterText()
     *
     * situation: if the CustomerSegment module is disabled, the generated filter text should be empty
     */
    public function testForDisabledGenerateFilterText()
    {
        // claim that the CustomerSegment module is disabled
        $this->segmentHelper->expects($this->any())->method('isEnabled')->willReturn(false);

        /** @var \Magento\Quote\Model\Quote\Address|\PHPUnit\Framework\MockObject\MockObject $quoteAddress */
        $quoteAddress = $this->buildQuoteAddress();

        $filterText = $this->filterTextGenerator->generateFilterText($quoteAddress);
        $this->assertEmpty($filterText, "Expected 'filterText' to be empty when CustomerSegment module is disabled");
    }

    /**
     * test: generateFilterText()
     *
     * situation: typical usage
     */
    public function testGenerateFilterText()
    {
        $segmentIds = [1, 2, 3, 4, 3, 5]; // sample includes duplicates

        // flesh out mocks
        $this->segmentHelper->expects($this->any())->method('isEnabled')->willReturn(true);
        $this->segmentCustomer->expects($this->any())
            ->method('getCustomerSegmentIdsForWebsite')
            ->willReturn($segmentIds);
        /** @var \Magento\Quote\Model\Quote\Address|\PHPUnit\Framework\MockObject\MockObject $quoteAddress */
        $quoteAddress = $this->buildQuoteAddress();

        // test
        $filterText = $this->filterTextGenerator->generateFilterText($quoteAddress);
        $this->verifyResults($filterText, $segmentIds);
    }

    // --- helpers ------------------------------------------------------------

    protected function verifyResults(array $filterText, array $segmentIds)
    {
        // gather all the unique segment ids
        $uniqueIds = [];
        foreach ($segmentIds as $id) {
            if (!in_array($id, $uniqueIds)) {
                $uniqueIds[] = $id;
            }
        }

        // verify all the combinations are present
        $missingIds = [];
        foreach ($uniqueIds as $id) {
            $token = (string)$id;
            if (!$this->findMe($token, $filterText)) {
                $missingIds[] = $token;
            }
        }
        if (sizeof($missingIds)) {
            $this->fail("'filterText' is missing the following segment ids: " . print_r($missingIds, true));
        }

        // verify same size of the unique ids array and the results array
        $this->assertEquals(
            sizeof($uniqueIds),
            sizeof($filterText),
            "Expected size of 'uniqueIds' to be the same as 'filterText'"
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

    protected function buildQuoteAddress()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getStore']);
        $quoteMock->method('getStore')->willReturn($storeMock);
        $quoteAddress = $this->createPartialMock(\Magento\Quote\Model\Quote\Address::class, ['getQuote']);
        $quoteAddress->method('getQuote')->willReturn($quoteMock);
        return $quoteAddress;
    }
}
