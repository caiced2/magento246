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

use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\ResourceModel\Group;
use Magento\TargetRule\Model\Indexer\TargetRule\Plugin\StoreGroup;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StoreGroupTest extends TestCase
{
    /**
     * @var StoreGroup
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
        $this->_model = new StoreGroup(
            $this->_productRuleMock,
            $this->_ruleProductMock
        );
    }

    public function testCategoryChanges()
    {
        $subjectMock = $this->getMockBuilder(Group::class)
            ->addMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $modelMock = $this->createMock(AbstractModel::class);

        $subjectMock->expects($this->any())
            ->method('getData')
            ->willReturn(11);

        $this->_productRuleMock->expects($this->once())
            ->method('markIndexerAsInvalid');

        $this->_ruleProductMock->expects($this->once())
            ->method('markIndexerAsInvalid');

        $this->_model->beforeSave($subjectMock, $modelMock);
    }
}
