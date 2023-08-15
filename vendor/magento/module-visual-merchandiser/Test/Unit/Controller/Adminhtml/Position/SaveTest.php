<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Controller\Adminhtml\Position;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\VisualMerchandiser\Controller\Adminhtml\Position\Save;
use Magento\VisualMerchandiser\Model\Position\Cache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveTest extends TestCase
{
    /**
     * @var Save
     */
    protected $controller;

    /**
     * @var Http|MockObject
     */
    protected $requestMock;

    /**
     * @var Json|MockObject
     */
    protected $resultJson;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * Set up instances and mock objects
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(Http::class);

        $this->resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $resultJsonFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->resultJson);

        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $helper = new ObjectManager($this);

        $context = $this->getMockBuilder(Context::class)
            ->setMethods(['getRequest'])
            ->setConstructorArgs($helper->getConstructArguments(Context::class))
            ->getMock();

        $cache = $this->getMockBuilder(Cache::class)
            ->setConstructorArgs(
                $helper->getConstructArguments(Cache::class)
            )
            ->getMock();
        $context->expects($this->once())->method('getRequest')->willReturn($this->requestMock);

        $this->controller = (new ObjectManager($this))->getObject(
            Save::class,
            [
                'context' => $context,
                'cache' => $cache,
                'resultJsonFactory' => $resultJsonFactory,
                'jsonDecoder' => $this->serializer
            ]
        );
    }

    /**
     * Test execute method
     */
    public function testExecute()
    {
        $this->assertInstanceOf(
            Json::class,
            $this->controller->execute()
        );
    }
}
