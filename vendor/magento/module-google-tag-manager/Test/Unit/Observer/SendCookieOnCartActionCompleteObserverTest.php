<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Observer;

use Magento\Framework\App\RequestInterface as Request;
use Magento\Framework\Event\Observer;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GoogleTagManager\Helper\Data as DataHelper;
use Magento\GoogleTagManager\Observer\SendCookieOnCartActionCompleteObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SendCookieOnCartActionCompleteObserverTest extends TestCase
{
    /** @var SendCookieOnCartActionCompleteObserver */
    private $model;

    /** @var ObjectManagerHelper */
    private $objectManagerHelper;

    /** @var Request */
    private $request;

    /**
     * @var DataHelper|MockObject
     */
    private $helper;

    /**
     * @var Registry|MockObject
     */
    private $registry;

    /**
     * @var CookieManagerInterface|MockObject
     */
    private $cookieManager;

    /**
     * @var JsonHelper|MockObject
     */
    private $jsonHelper;

    /**
     * @var CookieMetadataFactory|MockObject
     */
    private $cookieMetadataFactory;

    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @var PublicCookieMetadata|MockObject
     */
    private $publicCookieMetaData;

    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(DataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['registry'])
            ->getMock();

        $this->cookieManager = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->jsonHelper = $this->getMockBuilder(JsonHelper::class)
            ->setMethods(['jsonEncode'])
            ->disableOriginalConstructor()
            ->getMock();
        // PHP 8.1 compatibility
        $this->jsonHelper
            ->method('jsonEncode')
            ->willReturn('');

        $this->cookieMetadataFactory = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->publicCookieMetaData = $this->getMockBuilder(PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isXmlHttpRequest',
                'getModuleName',
                'setModuleName',
                'getActionName',
                'setActionName',
                'getParam',
                'setParams',
                'getParams',
                'getCookie',
                'isSecure'
            ])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->model = $this->objectManagerHelper->getObject(
            SendCookieOnCartActionCompleteObserver::class,
            [
                'helper' => $this->helper,
                'registry' => $this->registry,
                'cookieManager' => $this->cookieManager,
                'jsonHelper' => $this->jsonHelper,
                'cookieMetadataFactory' => $this->cookieMetadataFactory,
                'request' => $this->request
            ]
        );
    }

    public function testExecuteWithAvailableTagManager()
    {
        $this->helper->expects($this->once())
            ->method('isTagManagerAvailable')
            ->willReturn(false);

        $this->assertSame($this->model, $this->model->execute($this->observer));
    }

    /**
     * @param array $productsToAdd
     * @param bool $isXmlHttpRequest
     * @param string $setPublicCookie
     *
     * @dataProvider addToCartCookieDataProvider
     */
    public function testExecuteAddToCart(array $productsToAdd, $isXmlHttpRequest, $setPublicCookie)
    {
        $this->helper->expects($this->once())
            ->method('isTagManagerAvailable')
            ->willReturn(true);

        $this->request->expects($this->once())
            ->method('isXmlHttpRequest')
            ->willReturn($isXmlHttpRequest);

        $this->registry->expects($this->atLeastOnce())
            ->method('registry')
            ->willReturnMap(
                [
                    ['GoogleTagManager_products_addtocart', $productsToAdd],
                    ['GoogleTagManager_products_to_remove', null]
                ]
            );

        $this->cookieMetadataFactory->expects($this->once())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetaData);

        $this->publicCookieMetaData->expects($this->once())
            ->method('setDuration')
            ->willReturnSelf();
        $this->publicCookieMetaData->expects($this->once())
            ->method('setPath')
            ->willReturnSelf();
        $this->publicCookieMetaData->expects($this->once())
            ->method('setHttpOnly')
            ->willReturnSelf();
        $this->publicCookieMetaData->expects($this->once())
            ->method('setSameSite')
            ->willReturnSelf();
        $this->cookieManager->expects($this->{$setPublicCookie}())
            ->method('setPublicCookie')
            ->willReturnSelf();

        $this->assertSame($this->model, $this->model->execute($this->observer));
    }

    public function addToCartCookieDataProvider()
    {
        $productsToAdd = [
            [
                'sku' => 'iphone',
                'name' => 'Iphone',
                'price' => '650',
                'qty' => 1
            ]
        ];

        return [
            [$productsToAdd, true, 'never'],
            [$productsToAdd, false, 'once'],
        ];
    }

    /**
     * @param array $productsToRemove
     * @param bool $isXmlHttpRequest
     * @param string $setPublicCookie
     *
     * @dataProvider removeFromCartCookieDataProvider
     */
    public function testExecuteRemoveFromCart(array $productsToRemove, $isXmlHttpRequest, $setPublicCookie)
    {
        $this->helper->expects($this->once())
            ->method('isTagManagerAvailable')
            ->willReturn(true);

        $this->request->expects($this->once())
            ->method('isXmlHttpRequest')
            ->willReturn($isXmlHttpRequest);

        $this->registry->expects($this->atLeastOnce())
            ->method('registry')
            ->willReturnMap(
                [
                    ['GoogleTagManager_products_addtocart', null],
                    ['GoogleTagManager_products_to_remove', $productsToRemove]
                ]
            );

        $this->cookieMetadataFactory->expects($this->once())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetaData);

        $this->publicCookieMetaData->expects($this->once())
            ->method('setDuration')
            ->willReturnSelf();
        $this->publicCookieMetaData->expects($this->once())
            ->method('setPath')
            ->willReturnSelf();
        $this->publicCookieMetaData->expects($this->once())
            ->method('setHttpOnly')
            ->willReturnSelf();
        $this->publicCookieMetaData->expects($this->once())
            ->method('setSameSite')
            ->willReturnSelf();

        $this->cookieManager->expects($this->{$setPublicCookie}())
            ->method('setPublicCookie')
            ->willReturnSelf();

        $this->assertSame($this->model, $this->model->execute($this->observer));
    }

    public function removeFromCartCookieDataProvider()
    {
        $productsToRemove = [
            [
                'sku' => 'iphone',
                'name' => 'Iphone',
                'price' => '650',
                'qty' => 1
            ]
        ];

        return [
            [$productsToRemove, false, 'once'],
            [$productsToRemove, true, 'never']
        ];
    }
}
