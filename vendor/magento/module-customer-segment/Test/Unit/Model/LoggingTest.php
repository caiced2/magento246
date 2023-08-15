<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\CustomerSegment\Model\Logging;
use Magento\Framework\Simplexml\Element;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Logging\Model\Event;

/**
 * Test matching customer logging
 */
class LoggingTest extends TestCase
{
    /**
     * Test matching customer logging
     *
     * @param int|null $customerSegmentId
     * @param string $expectedText
     * @return void
     * @dataProvider postDispatchCustomerSegmentMatchDataProvider
     */
    public function testPostDispatchCustomerSegmentMatch($customerSegmentId, $expectedText): void
    {
        $objectManager = new ObjectManager($this);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getParam')
            ->with('id')
            ->willReturn($customerSegmentId);
        $model = $objectManager->getObject(Logging::class, ['request' => $requestMock]);
        $config = $objectManager->getObject(Element::class, ['data' => '<config/>']);
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['setInfo'])
            ->onlyMethods(['__wakeup'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->any())->method('setInfo')->with($expectedText);

        $model->postDispatchCustomerSegmentMatch($config, $eventMock);
    }

    /**
     * Data for testPostDispatchCustomerSegmentMatch
     *
     * @return array[]
     */
    public function postDispatchCustomerSegmentMatchDataProvider(): array
    {
        return [
            'specific segment' => [1,'Matching Customers of Segment 1 is added to messages queue.'],
            'no segment' => [null, '-']
        ];
    }
}
