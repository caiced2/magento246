<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GoogleTagManager\Helper\Data as DataHelper;
use Magento\GoogleTagManager\Observer\AddToCartWithRedirect;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddToCartWitRedirectTest extends TestCase
{
    /**
     * @var AddToCartWithRedirect
     */
    private $model;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var DataHelper|MockObject
     */
    private $helper;

    /**
     * @var CookieManagerInterface|MockObject
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory|MockObject
     */
    private $cookieMetadataFactory;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @var Product|MockObject
     */
    private $product;

    /**
     * @var Event|MockObject
     */
    private $event;

    /**
     * @var PublicCookieMetadata|MockObject
     */
    private $publicCookieMetadata;

    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(DataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieManager = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPublicCookie'])
            ->getMockForAbstractClass();

        $this->publicCookieMetadata = $this->getMockBuilder(PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->setMethods(['setDuration', 'setPath', 'setHttpOnly', 'setSameSite'])
            ->getMock();
        $this->publicCookieMetadata->expects($this->any())
            ->method('setDuration')
            ->willReturn($this->publicCookieMetadata);
        $this->publicCookieMetadata->expects($this->any())
            ->method('setPath')
            ->willReturn($this->publicCookieMetadata);
        $this->publicCookieMetadata->expects($this->any())
            ->method('setHttpOnly')
            ->willReturn($this->publicCookieMetadata);
        $this->publicCookieMetadata->expects($this->any())
            ->method('setSameSite')
            ->willReturn($this->publicCookieMetadata);
        $this->cookieMetadataFactory = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['createPublicCookieMetadata'])
            ->getMock();
        $this->cookieMetadataFactory->expects($this->any())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadata);

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku', 'getName', 'getPrice', 'getQty'])
            ->getMock();

        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMock();
        $this->event->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->product);

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent'])
            ->getMock();
        $this->observer->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->model = $this->objectManagerHelper->getObject(
            AddToCartWithRedirect::class,
            [
                'helper' => $this->helper,
                'cookieManager' => $this->cookieManager,
                'cookieMetadataFactory' => $this->cookieMetadataFactory,
                'scopeConfig' => $this->scopeConfig
            ]
        );
    }

    public function testExecuteWithoutAvailableTagManager()
    {
        $this->helper->expects($this->once())
            ->method('isTagManagerAvailable')
            ->willReturn(false);

        $this->model->execute($this->observer);
    }

    public function testExecuteWithoutEnabledRedirect()
    {
        $this->helper->expects($this->once())
            ->method('isTagManagerAvailable')
            ->willReturn(true);

        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('checkout/cart/redirect_to_cart', ScopeInterface::SCOPE_STORE)
            ->willReturn(false);

        $this->model->execute($this->observer);
    }

    public function testExecute()
    {
        $productSku = 'sku';
        $productName = 'name';
        $productPrice = 1.01;
        $productQty = 1;
        $productsToAdd = [
            [
                'sku' => $productSku,
                'name' => $productName,
                'price' => $productPrice,
                'qty' => $productQty,
            ]
        ];

        $this->product->expects($this->once())
            ->method('getSku')
            ->willReturn($productSku);
        $this->product->expects($this->once())
            ->method('getName')
            ->willReturn($productName);
        $this->product->expects($this->once())
            ->method('getPrice')
            ->willReturn($productPrice);
        $this->product->expects($this->once())
            ->method('getQty')
            ->willReturn($productQty);

        $this->helper->expects($this->once())
            ->method('isTagManagerAvailable')
            ->willReturn(true);

        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('checkout/cart/redirect_to_cart', ScopeInterface::SCOPE_STORE)
            ->willReturn(true);

        $this->cookieManager->expects($this->once())
            ->method('setPublicCookie')
            ->with(
                DataHelper::GOOGLE_ANALYTICS_COOKIE_NAME,
                \rawurlencode(\json_encode($productsToAdd)),
                $this->publicCookieMetadata
            );

        $this->model->execute($this->observer);
    }
}
