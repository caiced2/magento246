<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Checkout\Model\Cart;
use Magento\Framework\DataObject;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\GiftRegistry\Model\Item;
use Magento\GiftRegistry\Model\Item\OptionFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemTest extends TestCase
{
    /**
     * GiftRegistry item instance
     *
     * @var Item
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $cartMock;

    /**
     * @var MockObject
     */
    protected $productMock;

    /**
     * @var MockObject
     */
    protected $messageManagerMock;

    /**
     * @var MockObject
     */
    protected $catalogUrlMock;

    /**
     * @var MockObject
     */
    private $serializerMock;

    protected function setUp(): void
    {
        $contextMock = $this->createMock(Context::class);
        $registryMock = $this->createMock(Registry::class);
        $productRepositoryMock = $this->createMock(ProductRepository::class);
        $itemOptionMock = $this->createPartialMock(OptionFactory::class, ['create']);
        $this->catalogUrlMock = $this->createMock(Url::class);
        $this->messageManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->cartMock = $this->createMock(Cart::class);

        $resourceMock = $this->getMockBuilder(AbstractResource::class)
            ->addMethods(['getIdFieldName'])
            ->onlyMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['setUrlDataObject', 'getUrlDataObject'])
            ->onlyMethods(
                [
                    'getStatus',
                    'getName',
                    'getId',
                    'isVisibleInSiteVisibility',
                    'addCustomOption',
                    'getVisibleInSiteVisibilities',
                    'getStoreId',
                    'isSalable',
                    '__sleep'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializerMock = $this->createMock(Json::class);

        $this->model = new Item(
            $contextMock,
            $registryMock,
            $productRepositoryMock,
            $itemOptionMock,
            $this->catalogUrlMock,
            $this->messageManagerMock,
            $resourceMock,
            null,
            [],
            $this->serializerMock
        );
    }

    /**
     * @covers \Magento\GiftRegistry\Model\Item::__construct
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartProductDisabled()
    {
        $this->productMock->expects($this->once())->method('getStatus')
            ->willReturn(Status::STATUS_DISABLED);
        $this->productMock->expects($this->never())->method('isVisibleInSiteVisibility');

        $this->cartMock->expects($this->once())->method('getProductIds')->willReturn([]);

        $this->model->setData('product', $this->productMock);
        $this->assertFalse($this->model->addToCart($this->cartMock, 1));
    }

    /**
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartRequestedQuantityExceeded()
    {
        $this->productMock->expects($this->once())->method('getStatus')
            ->willReturn(Status::STATUS_DISABLED);

        $this->productMock->expects($this->once())->method('getName')->willReturn('Product');
        $this->cartMock->expects($this->once())->method('getProductIds')->willReturn([]);

        $this->model->setData('product', $this->productMock);
        $this->model->setData('qty', 1);
        $this->model->setData('qty_fullfilled', 0);

        $this->messageManagerMock->expects($this->once())
            ->method('addNotice')
            ->with(
                'The quantity of "Product" product added to cart exceeds the quantity desired by '
                . 'the Gift Registry owner. The quantity added has been adjusted to meet remaining quantity 1.'
            );

        $this->assertFalse($this->model->addToCart($this->cartMock, 5));
    }

    /**
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartThatAlreadyContainsQuantityThatExceedsRequested()
    {
        $registryItemId = 17;
        $productId = 1;
        $itemProductQty = 1;
        $registryQty = 1;
        $fullFilledQty = 0;
        $requestedQty = 5;

        $quoteMock = $this->createMock(Quote::class);
        $itemMock = $this->getMockBuilder(AbstractItem::class)
            ->setMethods(['getGiftregistryItemId', 'getProduct', 'getQty'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productMock->expects($this->once())->method('getStatus')
            ->willReturn(Status::STATUS_DISABLED);
        $this->productMock->expects($this->any())->method('getName')->willReturn('Product');
        $this->productMock->expects($this->any())->method('getId')->willReturn($productId);

        $this->cartMock->expects($this->once())->method('getProductIds')->willReturn([$productId]);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($quoteMock);

        $itemMock->expects($this->any())->method('getProduct')->willReturn($this->productMock);
        $itemMock->expects($this->any())->method('getGiftregistryItemId')->willReturn($registryItemId);
        $itemMock->expects($this->any())->method('getQty')->willReturn($itemProductQty);

        $quoteMock->expects($this->once())->method('getAllItems')->willReturn([$itemMock]);

        $this->model->setData('product', $this->productMock);
        $this->model->setData('qty', $registryQty);
        $this->model->setData('qty_fullfilled', $fullFilledQty);
        $this->model->setData('id', $registryItemId);

        $this->messageManagerMock->expects($this->exactly(2))->method('addNotice');
        $this->assertFalse($this->model->addToCart($this->cartMock, $requestedQty));
    }

    /**
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartProductInvisibleForCurrentStore()
    {
        $storeId = 3;

        $this->productMock->expects($this->once())->method('getStatus')
            ->willReturn(Status::STATUS_ENABLED);
        $this->productMock->expects($this->once())->method('isVisibleInSiteVisibility')
            ->willReturn(false);
        $this->productMock->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->cartMock->expects($this->once())->method('getProductIds')->willReturn([]);

        $this->model->setData('product', $this->productMock);
        $this->model->setData('store_id', $storeId);
        $this->model->setData('qty', 1);

        $this->messageManagerMock->expects($this->never())->method('addNotice');
        $this->assertFalse($this->model->addToCart($this->cartMock, 1));
    }

    /**
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartProductFromOtherStoreWithoutUrlRewrites()
    {
        $productStoreId = 3;
        $registryStoreId = 2;
        $productId = 1;

        $this->productMock->expects($this->once())->method('getStatus')
            ->willReturn(Status::STATUS_ENABLED);
        $this->productMock->expects($this->once())->method('isVisibleInSiteVisibility')
            ->willReturn(false);
        $this->productMock->expects($this->any())->method('getStoreId')->willReturn($productStoreId);
        $this->productMock->expects($this->any())->method('getId')->willReturn($productId);
        $this->productMock->expects($this->never())->method('setUrlDataObject');

        $this->cartMock->expects($this->once())->method('getProductIds')->willReturn([]);

        $this->catalogUrlMock->expects($this->once())->method('getRewriteByProductStore')
            ->with([$productId => $registryStoreId])->willReturn([]);

        $this->model->setData('product', $this->productMock);
        $this->model->setData('store_id', $registryStoreId);
        $this->model->setData('qty', 1);

        $this->messageManagerMock->expects($this->never())->method('addNotice');
        $this->assertFalse($this->model->addToCart($this->cartMock, 1));
    }

    /**
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartProductUrlIsNotVisibleInSite()
    {
        $productStoreId = 3;
        $registryStoreId = 2;
        $productId = 1;

        $objectMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getVisibility'])
            ->disableOriginalConstructor()
            ->getMock();
        $objectMock->expects($this->once())->method('getVisibility')->willReturn(1);

        $this->productMock->expects($this->once())->method('getStatus')
            ->willReturn(Status::STATUS_ENABLED);
        $this->productMock->expects($this->once())->method('isVisibleInSiteVisibility')
            ->willReturn(false);
        $this->productMock->expects($this->any())->method('getStoreId')->willReturn($productStoreId);
        $this->productMock->expects($this->any())->method('getId')->willReturn($productId);
        $this->productMock->expects($this->once())->method('setUrlDataObject');
        $this->productMock->expects($this->once())->method('getUrlDataObject')->willReturn($objectMock);
        $this->productMock->expects($this->once())->method('getVisibleInSiteVisibilities')
            ->willReturn([]);
        $this->productMock->expects($this->never())->method('isSalable');

        $this->cartMock->expects($this->once())->method('getProductIds')->willReturn([]);

        $this->catalogUrlMock->expects($this->once())->method('getRewriteByProductStore')
            ->with([$productId => $registryStoreId])->willReturn([$productId => $productId]);

        $this->model->setData('product', $this->productMock);
        $this->model->setData('store_id', $registryStoreId);
        $this->model->setData('qty', 1);

        $this->messageManagerMock->expects($this->never())->method('addNotice');
        $this->assertFalse($this->model->addToCart($this->cartMock, 1));
    }

    /**
     *
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartProductNotSalable()
    {
        $this->expectException('Magento\CatalogInventory\Model\StockStateException');
        $this->expectExceptionMessage('This product(s) is out of stock.');
        $this->productMock->expects($this->once())->method('getStatus')
            ->willReturn(Status::STATUS_ENABLED);
        $this->productMock->expects($this->once())->method('isVisibleInSiteVisibility')
            ->willReturn(true);
        $this->productMock->expects($this->any())->method('isSalable')->willReturn(false);

        $this->cartMock->expects($this->once())->method('getProductIds')->willReturn([]);

        $this->model->setData('product', $this->productMock);
        $this->model->setData('qty', 1);

        $this->model->addToCart($this->cartMock, 1);
    }
}
