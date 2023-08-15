<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor;
use Magento\TargetRule\Observer\CoreConfigSaveCommitAfterObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CoreConfigSaveCommitAfterObserverTest extends TestCase
{
    /**
     * Tested observer
     *
     * @var CoreConfigSaveCommitAfterObserver
     */
    protected $_observer;

    /**
     * Product-Rule processor mock
     *
     * @var Processor|MockObject
     */
    protected $_productRuleProcessorMock;

    protected function setUp(): void
    {
        $this->_productRuleProcessorMock = $this->createMock(
            Processor::class
        );

        $this->_observer = (new ObjectManager($this))->getObject(
            CoreConfigSaveCommitAfterObserver::class,
            [
                'productRuleIndexerProcessor' => $this->_productRuleProcessorMock,
            ]
        );
    }

    public function testCoreConfigSaveCommitAfter()
    {
        $observerMock = $this->getMockBuilder(Observer::class)
            ->addMethods(['getDataObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $dataObject = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getPath', 'isValueChanged'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataObject->expects($this->once())
            ->method('getPath')
            ->willReturn('customer/magento_customersegment/is_enabled');

        $dataObject->expects($this->once())
            ->method('isValueChanged')
            ->willReturn(true);

        $observerMock->expects($this->exactly(2))
            ->method('getDataObject')
            ->willReturn($dataObject);

        $this->_productRuleProcessorMock->expects($this->once())
            ->method('markIndexerAsInvalid');

        $this->_observer->execute($observerMock);
    }

    public function testCoreConfigSaveCommitAfterNoChanges()
    {
        $observerMock = $this->getMockBuilder(Observer::class)
            ->addMethods(['getDataObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $dataObject = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getPath', 'isValueChanged'])
            ->disableOriginalConstructor()
            ->getMock();
        $dataObject->expects($this->once())
            ->method('getPath')
            ->willReturn('customer/magento_customersegment/is_enabled');

        $dataObject->expects($this->once())
            ->method('isValueChanged')
            ->willReturn(false);

        $observerMock->expects($this->exactly(2))
            ->method('getDataObject')
            ->willReturn($dataObject);

        $this->_productRuleProcessorMock->expects($this->never())
            ->method('markIndexerAsInvalid');

        $this->_observer->execute($observerMock);
    }
}
