<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\GiftWrapping\Helper\Data;
use Magento\GiftWrapping\Model\ConfigProvider;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory;
use Magento\GiftWrapping\Model\Wrapping;
use Magento\Quote\Model\Cart\Totals;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\ResourceModel\Quote\Item as QuoteItem;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigProviderTest extends TestCase
{
    /** @var Session|MockObject */
    private $checkoutSession;

    /** @var \Magento\Checkout\Model\CartFactory|MockObject */
    private $checkoutCartFactory;

    /** @var ProductRepositoryInterface|MockObject */
    private $productRepository;

    /** @var Data|MockObject */
    private $giftWrappingData;

    /** @var StoreManagerInterface|MockObject */
    private $storeManager;

    /** @var CollectionFactory|MockObject */
    private $wrappingCollectionFactory;

    /** @var Collection|MockObject */
    private $wrappingCollection;

    /** @var Wrapping|MockObject */
    private $wrappingItem;

    /** @var RequestInterface|MockObject */
    private $request;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var \Magento\Framework\Pricing\Helper\Data|MockObject */
    private $pricingHelper;

    /** @var UrlInterface|MockObject */
    private $urlBuilder;

    /** @var Repository|MockObject */
    private $assetRepo;

    /** @var ConfigProvider */
    private $provider;

    /** @var \Magento\Quote\Model\QuoteIdMaskFactory|MockObject*/
    private $quoteIdMaskFactory;

    /** @var QuoteIdMask|MockObject */
    private $quoteIdMask;

    /** @var  Totals|MockObject  */
    private $totalsMock;

    /** @var TaxClassKeyInterfaceFactory|MockObject  */
    private $taxClassKeyFactory;

    /** @var TaxClassKeyInterface|MockObject  */
    private $taxClassKey;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->totalsMock = $this->getMockBuilder(Totals::class)
            ->addMethods(['getGwCardPrice'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSession = $this->createMock(Session::class);
        $this->checkoutCartFactory = $this->createPartialMock(\Magento\Checkout\Model\CartFactory::class, ['create']);
        $this->giftWrappingData = $this->createMock(Data::class);
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class, [], '', false);
        $this->assetRepo = $this->createMock(Repository::class);

        $this->request = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false
        );
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class, [], '', false);
        $this->pricingHelper = $this->createMock(\Magento\Framework\Pricing\Helper\Data::class);
        $this->productRepository = $this->getMockForAbstractClass(
            ProductRepositoryInterface::class,
            [],
            '',
            false
        );
        $this->storeManager = $this->getMockForAbstractClass(
            StoreManagerInterface::class,
            [],
            '',
            false
        );
        $this->wrappingCollectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->wrappingCollection = $this->createMock(
            Collection::class
        );
        $this->wrappingItem = $this->getMockBuilder(Wrapping::class)
            ->addMethods(['setTaxClassKey'])
            ->onlyMethods(['getBasePrice', 'getImageUrl', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteIdMaskFactory = $this->createPartialMock(
            \Magento\Quote\Model\QuoteIdMaskFactory::class,
            ['create']
        );
        $this->quoteIdMask = $this->getMockBuilder(QuoteIdMask::class)
            ->addMethods(['getMaskedId'])
            ->onlyMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->taxClassKeyFactory = $this->createPartialMock(
            \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory::class,
            ['create']
        );
        $this->taxClassKey = $this->getMockForAbstractClass(
            TaxClassKeyInterface::class,
            [],
            '',
            false
        );

        $objectManager = new ObjectManager($this);
        $this->provider = $objectManager->getObject(
            ConfigProvider::class,
            [
                'checkoutCartFactory' => $this->checkoutCartFactory,
                'productRepository' => $this->productRepository,
                'giftWrappingData' => $this->giftWrappingData,
                'storeManager' => $this->storeManager,
                'wrappingCollectionFactory' => $this->wrappingCollectionFactory,
                'urlBuilder' => $this->urlBuilder,
                'assetRepo' => $this->assetRepo,
                'request' => $this->request,
                'logger' => $this->logger,
                'checkoutSession' => $this->checkoutSession,
                'pricingHelper' => $this->pricingHelper,
                'quoteIdMaskFactory' => $this->quoteIdMaskFactory,
                'taxClassKeyFactory' => $this->taxClassKeyFactory
            ]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetConfig()
    {
        $address = $this->createMock(Address::class);
        $address->expects($this->any())->method('getId')->willReturn(2);
        $shippingAddressMock = $this->createMock(Address::class);
        $productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getGiftWrappingAvailable', 'getGiftWrappingPrice'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItemMock = $this->getMockBuilder(QuoteItem::class)
            ->addMethods(['getProduct', 'getParentItem', 'getId', 'setTaxClassKey'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getIsMultiShipping', 'hasGwId', 'getGwId'])
            ->onlyMethods(
                ['getAllShippingAddresses', 'getBillingAddress', 'getTotals', 'getShippingAddress', 'getAllItems']
            )
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->atLeastOnce())->method('getAllShippingAddresses')->willReturn([$address]);
        $quote->expects($this->any())->method('getIsMultiShipping')->willReturn(true);
        $quote->expects($this->atLeastOnce())->method('getBillingAddress')->willReturn($address);
        $quote->expects($this->atLeastOnce())->method('hasGwId')->willReturn(true);
        $quote->expects($this->atLeastOnce())->method('getGwId')->willReturn(3);
        $quote->expects($this->once())->method('getShippingAddress')->willReturn($shippingAddressMock);
        $quote->expects($this->once())->method('getAllItems')->willReturn([$quoteItemMock]);
        $this->checkoutSession->expects($this->any())->method('getQuote')->willReturn($quote);

        $cartItems = $this->createCartItemMocks();
        $checkoutCart = $this->createMock(Cart::class);
        $checkoutCart->expects($this->atLeastOnce())->method('getItems')->willReturn($cartItems);
        $this->checkoutCartFactory->expects($this->atLeastOnce())->method('create')->willReturn($checkoutCart);

        $this->productRepository->expects($this->never())->method('getById');

        $this->wrappingItem->expects($this->once())->method('getBasePrice')->willReturn('13');
        $this->wrappingItem->expects($this->atLeastOnce())->method('setTaxClassKey');
        $this->wrappingItem->expects($this->once())->method('getImageUrl')->willReturn('http://image-url.com');
        $this->wrappingItem->expects($this->any())->method('getId')->willReturn(83);
        $this->wrappingCollection->expects($this->once())->method('addStoreAttributesToResult')->willReturnSelf();
        $this->wrappingCollection->expects($this->once())->method('applyStatusFilter')->willReturnSelf();
        $this->wrappingCollection->expects($this->once())->method('applyWebsiteFilter')->willReturnSelf();
        $this->wrappingCollection->expects($this->once())->method('getItems')->willReturn([$this->wrappingItem]);
        $this->wrappingCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->wrappingCollection);

        $this->request->expects($this->once())->method('isSecure')->willReturn(true);

        $store = $this->createMock(Store::class);
        $store->expects($this->any())->method('getId')->willReturn(11);
        $store->expects($this->once())->method('getWebsiteId')->willReturn(21);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);

        $this->giftWrappingData->expects($this->atLeastOnce())->method('getPrice')->willReturn(73);
        $this->giftWrappingData->expects($this->any())->method('getPrintedCardPrice')->willReturn(23);
        $this->giftWrappingData->expects($this->atLeastOnce())->method('getWrappingTaxClass')->willReturn('tax-class');
        $this->giftWrappingData->expects($this->atLeastOnce())->method('isGiftWrappingAvailableForOrder');
        $this->giftWrappingData->expects($this->atLeastOnce())->method('isGiftWrappingAvailableForItems');
        $this->giftWrappingData->expects($this->atLeastOnce())->method('allowPrintedCard')->willReturn(true);
        $this->giftWrappingData->expects($this->atLeastOnce())->method('allowGiftReceipt');
        $this->giftWrappingData->expects($this->atLeastOnce())->method('allowGiftReceipt');
        $this->giftWrappingData->expects($this->atLeastOnce())->method('displayCartWrappingBothPrices')
            ->willReturn(false);
        $this->giftWrappingData->expects($this->atLeastOnce())->method('displayCartWrappingIncludeTaxPrice')
            ->willReturn(false);

        $this->quoteIdMask->expects($this->once())->method('load')->willReturnSelf();
        $this->quoteIdMask->expects($this->once())->method('getMaskedId')->willReturn('masked-id');

        $this->quoteIdMaskFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->quoteIdMask);

        $this->taxClassKey->expects($this->atLeastOnce())
            ->method('setType')
            ->with(TaxClassKeyInterface::TYPE_ID)
            ->willReturnSelf();
        $this->taxClassKey->expects($this->atLeastOnce())
            ->method('setValue')
            ->with('tax-class')
            ->willReturnSelf();
        $this->taxClassKeyFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->taxClassKey);
        $this->pricingHelper->expects($this->atLeastOnce())
            ->method('currency');

        $this->provider->getConfig();
    }

    /**
     * Create Cart Item mocks
     *
     * @return array
     */
    private function createCartItemMocks(): array
    {
        $product = $this->getMockBuilder(Product::class)
            ->addMethods(['getGiftWrappingAvailable'])
            ->disableOriginalConstructor()
            ->getMock();
        $product->method('getGiftWrappingAvailable')->willReturn(true);

        $item = $this->getMockBuilder(QuoteItem::class)
            ->addMethods(['hasGwId', 'getGwId', 'getId', 'getProduct', 'isDeleted'])
            ->disableOriginalConstructor()
            ->getMock();
        $item->method('isDeleted')->willReturn(false);
        $item->expects($this->once())->method('hasGwId')->willReturn(true);
        $item->expects($this->once())->method('getGwId')->willReturn(13);
        $item->expects($this->once())->method('getId')->willReturn(2);
        $item->expects($this->once())->method('getProduct')->willReturn($product);

        $itemDeleted = $this->getMockBuilder(QuoteItem::class)
            ->addMethods(['hasGwId', 'getGwId', 'getId', 'getProduct', 'isDeleted'])
            ->disableOriginalConstructor()
            ->getMock();
        $itemDeleted->method('isDeleted')->willReturn(true);
        $itemDeleted->expects($this->never())->method('hasGwId');
        $itemDeleted->expects($this->never())->method('getGwId');
        $itemDeleted->expects($this->never())->method('getId');
        $itemDeleted->expects($this->never())->method('getProduct');

        return [$item, $itemDeleted];
    }
}
