<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Test\Unit\Controller\Reporting;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\QuickCheckoutAdminPanel\Controller\Adminhtml\Reporting\Index;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\ReportingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Reporting proxy test
 */
class IndexTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ReportingService|MockObject
     */
    private $service;

    /**
     * @var JsonFactory|MockObject
     */
    private $jsonFactory;

    /**
     * @var Json|MockObject
     */
    private $expectedResult;

    /**
     * @var Index
     */
    private $action;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $contextMock = $this->createPartialMock(
            Context::class,
            ['getObjectManager', 'getResultFactory']
        );

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getmock();

        $this->service = $this->getMockBuilder(ReportingService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectedResult = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->expectedResult);

        $this->action = new Index(
            $contextMock,
            $this->request,
            $this->service,
            $this->jsonFactory
        );
    }

    public function testSuccess()
    {
        $this->givenAValidRequest();

        $expectedReport = ['new_accounts' => [], 'orders' => []];

        $this->service->expects($this->once())
            ->method('generate')
            ->willReturn($expectedReport);

        $this->expectedResult->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(200);

        $this->expectedResult->expects($this->once())
            ->method('setData')
            ->with($expectedReport);

        $this->whenTheActionIsExecuted();
    }

    public function testInvalidParams()
    {
        $this->givenAnInvalidRequest();

        $this->service->expects($this->never())
            ->method('generate');

        $this->expectedResult->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(400);

        $this->expectedResult->expects($this->once())
            ->method('setData')
            ->with([]);

        $this->whenTheActionIsExecuted();
    }

    public function testUnexpectedError()
    {
        $this->givenAValidRequest();

        $this->service->expects($this->once())
            ->method('generate')
            ->willThrowException(new Exception('Unexpected error'));

        $this->expectedResult->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(500);

        $this->expectedResult->expects($this->once())
            ->method('setData')
            ->with([]);

        $this->whenTheActionIsExecuted();
    }

    /**
     * @return void
     */
    public function givenAnInvalidRequest(): void
    {
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn([]);
    }

    /**
     * @return void
     */
    public function givenAValidRequest(): void
    {
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'start_date' => '2022-08-07',
                'end_date' => '2022-10-14',
            ]);
    }

    /**
     * @return void
     * @throws NotFoundException
     */
    public function whenTheActionIsExecuted(): void
    {
        $this->assertEquals($this->expectedResult, $this->action->execute());
    }
}
