<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Controller\Adminhtml\Category;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\Category;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\VisualMerchandiser\Controller\Adminhtml\Category\Grid;
use PHPUnit\Framework\TestCase;

/**
 * Abstract shared functionality for controller tests
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AbstractGrid extends TestCase
{
    /**
     * @var string
     */
    protected $controllerClass;

    /**
     * @var Grid
     */
    protected $gridController;

    /**
     * @var RawFactory
     */
    protected $rawFactory;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Category
     */
    protected $category;

    /**
     * @var DataObject
     */
    protected $block;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Set up instances and mock objects
     */
    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);

        $this->category = $this->createPartialMock(Category::class, ['setStoreId']);

        $this->objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->category);

        $this->request = $this->getMockForAbstractClass(RequestInterface::class);

        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $store = $this->getMockForAbstractClass(StoreInterface::class);

        $store
            ->expects($this->atLeastOnce())
            ->method('getCode')
            ->willReturn('currentStore');

        $this->context
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->context
            ->expects($this->any())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);

        $this->layoutFactory = $this->getMockBuilder(LayoutFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager
            ->expects($this->atLeastOnce())
            ->method('getStore')
            ->willReturn($store);

        $this->block = $this->getMockBuilder(DataObject::class)
            ->addMethods(['toHtml', 'setPositionCacheKey'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->block
            ->expects($this->atLeastOnce())
            ->method('toHtml')
            ->willReturn('block-html');

        $resultRaw = (new ObjectManager($this))->getObject(Raw::class);
        $this->rawFactory = $this->createPartialMock(
            RawFactory::class,
            ['create']
        );
        $this->rawFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($resultRaw);

        $registry = $this->createPartialMock(Registry::class, ['register']);
        $wysiwygConfig = $this->getMockBuilder(Config::class)
            ->addMethods(['setStoreId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager
            ->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    [Registry::class, $registry],
                    [Config::class, $wysiwygConfig]
                ]
            );

        $this->gridController = (new ObjectManager($this))->getObject(
            $this->controllerClass,
            [
                'context' => $this->context,
                'resultRawFactory' => $this->rawFactory,
                'layoutFactory' => $this->layoutFactory,
                'storeManager' => $this->storeManager,
            ]
        );
    }

    protected function progressTest($block, $id)
    {
        $layout = $this->getMockBuilder(DataObject::class)
            ->addMethods(['createBlock'])
            ->disableOriginalConstructor()
            ->getMock();
        $layout
            ->expects($this->any())
            ->method('createBlock')
            ->with(
                $block,
                $id
            )
            ->willReturn($this->block);

        $this->layoutFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($layout);

        $this->assertInstanceOf(
            Raw::class,
            $this->gridController->execute()
        );
    }
}
