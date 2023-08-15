<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Controller\Adminhtml\Targetrule;

use Magento\TargetRule\Controller\Adminhtml\Targetrule\Delete;
use Magento\TargetRule\Model\Rule;

class DeleteTest extends AbstractTest
{
    /**
     * @var Delete
     */
    protected $controller;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new Delete(
            $this->contextMock,
            $this->registryMock,
            $this->dateMock
        );
    }

    /**
     * @param int $ruleId
     * @param \PHPUnit\Framework\MockObject_Matcher_Invocation $loadCalls
     * @param \PHPUnit\Framework\MockObject_Matcher_Invocation $deleteCalls
     * @param \PHPUnit\Framework\MockObject_Stub $deleteWill
     * @param string $redirectPath
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute($ruleId, $loadCalls, $deleteCalls, $deleteWill, $redirectPath)
    {
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock
            ->expects($this->any())
            ->method('create')
            ->with(Rule::class)
            ->willReturn($ruleMock);

        $ruleMock
            ->expects($loadCalls)
            ->method('load')
            ->with($ruleId);
        $ruleMock
            ->expects($deleteCalls)
            ->method('delete')
            ->will($deleteWill);

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([['id', null, $ruleId]]);

        $this->responseMock
            ->expects($this->once())
            ->method('setRedirect')
            ->with($redirectPath);

        $this->controller->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        $exception = new \Exception('expected');
        return [
            [123, $this->once(), $this->once(), $this->returnSelf(), 'adminhtml/*/'],
            [false, $this->never(), $this->never(), $this->returnSelf(), 'adminhtml/*/'],
            [321, $this->once(), $this->once(), $this->throwException($exception), 'adminhtml/*/edit'],
        ];
    }
}
