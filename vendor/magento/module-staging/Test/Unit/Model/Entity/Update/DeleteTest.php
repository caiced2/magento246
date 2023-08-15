<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\Update;

use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Staging\Controller\Result\JsonFactory;
use Magento\Staging\Model\Entity\Update\Action\Pool;
use Magento\Staging\Model\Entity\Update\Action\Save\SaveAction;
use Magento\Staging\Model\Entity\Update\Delete;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeleteTest extends TestCase
{
    /** @var Delete */
    protected $delete;

    /** @var ManagerInterface|MockObject */
    protected $messageManager;

    /** @var JsonFactory|MockObject */
    protected $jsonFactory;

    /** @var Pool|MockObject */
    protected $actionPool;

    /** @var SaveAction|MockObject */
    protected $action;

    /** @var Json|MockObject */
    protected $resultJson;

    /** @var LoggerInterface|MockObject */
    protected $logger;

    /** @var string */
    private $entityName = 'entity name';

    protected function setUp(): void
    {
        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->actionPool = $this->getMockBuilder(Pool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->action = $this->getMockBuilder(SaveAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->delete = new Delete(
            $this->messageManager,
            $this->jsonFactory,
            $this->actionPool,
            $this->logger,
            $this->entityName
        );
    }

    public function testExecuteWithoutModeValue()
    {
        $params = [
            'stagingData' => [
            ]
        ];
        $this->actionPool->expects($this->never())
            ->method('getAction');
        $this->actionPool->expects($this->never())
            ->method('getExecutor')
            ->with($this->action)
            ->willReturnArgument(0);
        $exception = new LocalizedException(__('The \'mode\' value is unexpected.'));
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('The \'mode\' value is unexpected.');
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->jsonFactory->expects($this->once())
            ->method('create')
            ->with([], ['error' => true])
            ->willReturn($this->resultJson);
        $this->assertSame($this->resultJson, $this->delete->execute($params));
    }

    public function testExecuteWIthLocalizedException()
    {
        $params = [
            'stagingData' => [
                'mode' => 'save'
            ]
        ];

        $this->actionPool->expects($this->once())
            ->method('getAction')
            ->with($this->entityName, 'delete', 'save')
            ->willReturn($this->action);
        $this->actionPool->expects($this->once())
            ->method('getExecutor')
            ->with($this->action)
            ->willReturnArgument(0);
        $exception = new LocalizedException(__('Error'));
        $this->action->expects($this->once())
            ->method('execute')
            ->with($params)
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('Error');
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->jsonFactory->expects($this->once())
            ->method('create')
            ->with([], ['error' => true])
            ->willReturn($this->resultJson);
        $this->assertSame($this->resultJson, $this->delete->execute($params));
    }

    public function testExecuteWIthException()
    {
        $params = [
            'stagingData' => [
                'mode' => 'save'
            ]
        ];

        $exception = new \Exception('Something went wrong');
        $this->actionPool->expects($this->once())
            ->method('getAction')
            ->with($this->entityName, 'delete', 'save')
            ->willReturn($this->action);
        $this->actionPool->expects($this->once())
            ->method('getExecutor')
            ->with($this->action)
            ->willReturnArgument(0);
        $this->action->expects($this->once())
            ->method('execute')
            ->with($params)
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addException')
            ->with($exception, __('Something went wrong while removing the %1.', 'entity name'));
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);
        $this->jsonFactory->expects($this->once())
            ->method('create')
            ->with([], ['error' => true])
            ->willReturn($this->resultJson);
        $this->assertSame($this->resultJson, $this->delete->execute($params));
    }
}
