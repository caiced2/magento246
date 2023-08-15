<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Controller\Adminhtml\Products;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Messages;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\VisualMerchandiser\Controller\Adminhtml\Products\MassAssign;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassAssignTest extends TestCase
{
    /*
     * @var \Magento\VisualMerchandiser\Controller\Adminhtml\Products\MassAssign
     */
    protected $controller;

    /**
     * @var Context
     */
    protected $context;

    /**
     * Magento\Framework\DataObject
     */
    protected $response;

    /**
     * @var RequestInterface|MockObject
     */
    protected $request;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ProductInterface
     */
    protected $product;

    /**
     * @var Json|MockObject
     */
    protected $resultJson;

    /**
     * Set up instances and mock objects
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);

        $this->request = $this->getMockForAbstractClass(RequestInterface::class);

        $this->objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasMessages'])
            ->getMockForAbstractClass();

        $this->layout = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['initMessages'])
            ->getMockForAbstractClass();

        $this->resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJson
            ->expects($this->any())
            ->method('setJsonData')
            ->willReturn($this->resultJson);

        $this->product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->product
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productRepository
            ->expects($this->any())
            ->method('get')
            ->willReturn($this->product);

        $resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $resultJsonFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->resultJson);

        $this->context
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->context
            ->expects($this->any())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);

        $this->context
            ->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManager);

        $this->response = $this->getMockBuilder(DataObject::class)
            ->addMethods(['setError'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->response);

        $this->layoutFactory = $this->getMockBuilder(LayoutFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->layoutFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->layout);

        $messagesBlock = $this->getMockBuilder(Messages::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->layout
            ->expects($this->any())
            ->method('getMessagesBlock')
            ->willReturn($messagesBlock);

        $this->controller = (new ObjectManager($this))->getObject(
            MassAssign::class,
            [
                'context' => $this->context,
                'layoutFactory' => $this->layoutFactory,
                'resultJsonFactory' => $resultJsonFactory,
                'productRepository' => $this->productRepository
            ]
        );
    }

    /**
     * Test execute assign method
     */
    public function testExecuteAssign()
    {
        $map = [
            ['action', null, 'assign'],
            ['add_product_sku', null, '24-MB01']
        ];

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getParam')
            ->willReturnMap($map);

        $this->assertInstanceOf(
            Json::class,
            $this->controller->execute()
        );
    }

    /**
     * Test execute remove method
     */
    public function testExecuteRemove()
    {
        $map = [
            ['action', null, 'remove'],
            ['add_product_sku', null, '24-MB01']
        ];

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getParam')
            ->willReturnMap($map);

        $this->assertInstanceOf(
            Json::class,
            $this->controller->execute()
        );
    }
}
