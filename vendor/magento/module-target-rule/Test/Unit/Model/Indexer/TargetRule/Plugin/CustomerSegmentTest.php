<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Plugin;

use Magento\CustomerSegment\Model\Segment;
use Magento\TargetRule\Model\Indexer\TargetRule\Plugin\CustomerSegment;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerSegmentTest extends TestCase
{
    /**
     * @var CustomerSegment
     */
    protected $_model;

    /**
     * @var Processor|MockObject
     */
    protected $_ruleProductMock;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor|MockObject
     */
    protected $_productRuleMock;

    protected function setUp(): void
    {
        $this->_ruleProductMock = $this->createMock(
            Processor::class
        );
        $this->_productRuleMock = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor::class
        );
        $this->_model = new CustomerSegment(
            $this->_productRuleMock,
            $this->_ruleProductMock
        );
    }

    public function testCustomerSegmentChanges()
    {
        $subjectMock = $this->createMock(Segment::class);
        $this->_productRuleMock->expects($this->exactly(2))
            ->method('markIndexerAsInvalid');

        $this->_ruleProductMock->expects($this->exactly(2))
            ->method('markIndexerAsInvalid');

        $this->assertEquals(
            $subjectMock,
            $this->_model->afterDelete($subjectMock)
        );

        $this->assertEquals(
            $subjectMock,
            $this->_model->afterSave($subjectMock)
        );
    }
}
