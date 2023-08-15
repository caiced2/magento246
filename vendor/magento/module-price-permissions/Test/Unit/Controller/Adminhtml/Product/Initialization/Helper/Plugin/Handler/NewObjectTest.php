<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricePermissions\Test\Unit\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler;

use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\App\RequestInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\PricePermissions\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler\NewObject;
use Magento\PricePermissions\Helper\Data;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewObjectTest extends TestCase
{
    /**
     * @var NewObject
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $storeManagerMock;

    /**
     * @var MockObject
     */
    protected $requestMock;

    /**
     * @var MockObject
     */
    protected $pricePerDataMock;

    /**
     * @var MockObject
     */
    protected $productMock;

    protected function setUp(): void
    {
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->pricePerDataMock = $this->createMock(Data::class);
        $this->productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getPriceType', 'setGiftcardAmounts', 'setMsrpEnabled', 'setMsrpDisplayActualPriceType'])
            ->onlyMethods(['isObjectNew', 'getTypeId', 'setPrice'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pricePerDataMock->expects(
            $this->once()
        )->method(
            'getDefaultProductPriceString'
        )->willReturn(
            '0.00'
        );

        $this->model = new NewObject($this->storeManagerMock, $this->requestMock, $this->pricePerDataMock);
    }

    public function testHandleWithNotNewProduct()
    {
        $this->productMock->expects($this->once())->method('isObjectNew')->willReturn(false);
        $this->model->handle($this->productMock);
    }

    public function testHandleWithDynamicProductPrice()
    {
        $this->productMock->expects($this->once())->method('isObjectNew')->willReturn(true);
        $this->productMock->expects(
            $this->once()
        )->method(
            'getTypeId'
        )->willReturn(
            Type::TYPE_BUNDLE
        );
        $this->productMock->expects(
            $this->once()
        )->method(
            'getPriceType'
        )->willReturn(
            Price::PRICE_TYPE_DYNAMIC
        );

        $this->productMock->expects($this->never())->method('setPrice');

        $this->productMock->expects(
            $this->once()
        )->method(
            'setMsrpDisplayActualPriceType'
        )->with(
            \Magento\Msrp\Model\Product\Attribute\Source\Type\Price::TYPE_USE_CONFIG
        );

        $this->model->handle($this->productMock);
    }

    public function testHandleWithGiftCardProductType()
    {
        $this->productMock->expects($this->once())->method('isObjectNew')->willReturn(true);
        $this->productMock->expects(
            $this->any()
        )->method(
            'getTypeId'
        )->willReturn(
            Giftcard::TYPE_GIFTCARD
        );

        $this->productMock->expects($this->once())->method('setPrice')->with('0.0');

        $this->requestMock->expects($this->once())->method('getParam')->with('store')->willReturn(10);
        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn(5);
        $this->storeManagerMock->expects(
            $this->once()
        )->method(
            'getStore'
        )->with(
            10
        )->willReturn(
            $storeMock
        );

        $this->productMock->expects(
            $this->once()
        )->method(
            'setGiftcardAmounts'
        )->with(
            [['website_id' => 5, 'price' => 0.0, 'delete' => '']]
        );
        $this->productMock->expects(
            $this->once()
        )->method(
            'setMsrpDisplayActualPriceType'
        )->with(
            \Magento\Msrp\Model\Product\Attribute\Source\Type\Price::TYPE_USE_CONFIG
        );

        $this->model->handle($this->productMock);
    }

    public function testHandleWithNonGiftCardProductType()
    {
        $this->productMock->expects($this->once())->method('isObjectNew')->willReturn(true);
        $this->productMock->expects($this->any())->method('getTypeId')->willReturn('some product type');

        $this->productMock->expects($this->once())->method('setPrice')->with('0.0');

        $this->productMock->expects($this->never())->method('setGiftcardAmounts');

        $this->productMock->expects(
            $this->once()
        )->method(
            'setMsrpDisplayActualPriceType'
        )->with(
            \Magento\Msrp\Model\Product\Attribute\Source\Type\Price::TYPE_USE_CONFIG
        );

        $this->model->handle($this->productMock);
    }
}
