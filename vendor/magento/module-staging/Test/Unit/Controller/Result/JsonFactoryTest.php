<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Controller\Result;

use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Message\Collection;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Staging\Controller\Result\JsonFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JsonFactoryTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $objectManagerMock;

    /**
     * @var MockObject
     */
    protected $messageManagerMock;

    /**
     * @var JsonFactory
     */
    protected $factory;

    protected function setUp(): void
    {
        $this->objectManagerMock = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->messageManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);

        $this->factory = new JsonFactory($this->objectManagerMock, $this->messageManagerMock);
    }

    public function testCreate()
    {
        $messageText = 'Some message';
        $messages = $messageText . '<br/>' . $messageText . '<br/>';

        $messageCollectionMock = $this->createMock(Collection::class);

        $jsonMock = $this->createMock(Json::class);
        $this->objectManagerMock->expects($this->once())->method('create')->willReturn($jsonMock);

        $this->messageManagerMock->expects($this->once())
            ->method('getMessages')
            ->with(true)
            ->willReturn($messageCollectionMock);
        $messageMock = $this->getMockForAbstractClass(MessageInterface::class);
        $items = [$messageMock, $messageMock];
        $messageCollectionMock->expects($this->once())->method('getItems')->willReturn($items);

        $messageMock->expects($this->exactly(2))->method('toString')->willReturn($messageText);

        $jsonMock->expects($this->once())->method('setData')->with(['messages' => $messages]);
        $this->assertEquals($jsonMock, $this->factory->create());
    }
}
