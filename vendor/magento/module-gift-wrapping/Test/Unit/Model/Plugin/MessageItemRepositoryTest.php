<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftMessage\Api\Data\MessageExtensionInterface;
use Magento\GiftMessage\Api\Data\MessageInterface;
use Magento\GiftMessage\Api\ItemRepositoryInterface;
use Magento\GiftWrapping\Helper\Data as DataHelper;
use Magento\GiftWrapping\Model\Plugin\MessageItemRepository as MessageItemRepositoryPlugin;
use Magento\GiftWrapping\Model\Wrapping;
use Magento\GiftWrapping\Model\WrappingFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MessageItemRepositoryTest extends TestCase
{
    /**
     * @var MessageItemRepositoryPlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Product|MockObject
     */
    private $productMock;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var WrappingFactory|MockObject
     */
    private $wrappingFactoryMock;

    /**
     * @var DataHelper|MockObject
     */
    private $dataHelperMock;

    /**
     * @var ItemRepositoryInterface|MockObject
     */
    private $subjectMock;

    /**
     * @var MessageInterface|MockObject
     */
    private $giftMessageMock;

    /**
     * @var MessageExtensionInterface|MockObject
     */
    private $extensionAttributesMock;

    /**
     * @var CartInterface|MockObject
     */
    private $quoteMock;

    /**
     * @var CartItemInterface|MockObject
     */
    private $quoteItemMock;

    /**
     * @var Wrapping|MockObject
     */
    private $wrappingMock;

    protected function setUp(): void
    {
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGiftWrappingAvailable'])
            ->getMock();
        $this->quoteRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->wrappingFactoryMock = $this->getMockBuilder(WrappingFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->dataHelperMock = $this->getMockBuilder(DataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectMock = $this->getMockBuilder(ItemRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->giftMessageMock = $this->getMockBuilder(MessageInterface::class)
            ->getMockForAbstractClass();
        $this->extensionAttributesMock = $this->getMockBuilder(MessageExtensionInterface::class)
            ->setMethods(['getWrappingId'])
            ->getMockForAbstractClass();
        $this->quoteMock = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['getItemById'])
            ->getMockForAbstractClass();
        $this->quoteItemMock = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(['setGwId', 'save', 'getProduct'])
            ->getMockForAbstractClass();
        $this->wrappingMock = $this->getMockBuilder(Wrapping::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            MessageItemRepositoryPlugin::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'wrappingFactory' => $this->wrappingFactoryMock,
                'dataHelper' => $this->dataHelperMock
            ]
        );
    }

    public function testAfterSave()
    {
        $cartId = 7;
        $itemId = 11;
        $wrappingId = 23;
        $loadedWrappingId = 37;

        $this->giftMessageMock->expects(static::atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $this->dataHelperMock->expects(static::atLeastOnce())
            ->method('isGiftWrappingAvailableForItems')
            ->willReturn(true);
        $this->quoteItemMock->expects(static::atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->quoteRepositoryMock->expects(static::atLeastOnce())
            ->method('getActive')
            ->with($cartId, [])
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects(static::atLeastOnce())
            ->method('getItemById')
            ->with($itemId)
            ->willReturn($this->quoteItemMock);
        $this->extensionAttributesMock->expects(static::atLeastOnce())
            ->method('getWrappingId')
            ->willReturn($wrappingId);
        $this->wrappingFactoryMock->expects(static::atLeastOnce())
            ->method('create')
            ->willReturn($this->wrappingMock);
        $this->wrappingMock->expects(static::atLeastOnce())
            ->method('load')
            ->with($wrappingId, null)
            ->willReturnSelf();
        $this->wrappingMock->expects(static::atLeastOnce())
            ->method('getId')
            ->willReturn($loadedWrappingId);
        $this->quoteItemMock->expects(static::atLeastOnce())
            ->method('setGwId')
            ->with($loadedWrappingId)
            ->willReturnSelf();
        $this->quoteItemMock->expects(static::once())
            ->method('save')
            ->willReturnSelf();

        $this->assertTrue($this->plugin->afterSave($this->subjectMock, true, $cartId, $this->giftMessageMock, $itemId));
    }

    public function testAfterSaveNoExtensionAttributes()
    {
        $cartId = 7;
        $itemId = 11;
        $this->quoteItemMock->expects(static::atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->quoteRepositoryMock->expects(static::atLeastOnce())
            ->method('getActive')
            ->with($cartId, [])
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects(static::atLeastOnce())
            ->method('getItemById')
            ->with($itemId)
            ->willReturn($this->quoteItemMock);
        $this->giftMessageMock->expects(static::atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $this->dataHelperMock->expects(static::any())
            ->method('isGiftWrappingAvailableForItems')
            ->willReturn(true);
        $this->quoteItemMock->expects(static::never())
            ->method('save');

        $this->assertTrue($this->plugin->afterSave($this->subjectMock, true, 7, $this->giftMessageMock, 11));
    }

    public function testAfterSaveGiftWrappingNotAvailable()
    {
        $cartId = 7;
        $itemId = 11;
        $this->productMock->expects(static::atLeastOnce())
            ->method('getGiftWrappingAvailable')
            ->willReturn(null);
        $this->quoteItemMock->expects(static::atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->quoteRepositoryMock->expects(static::atLeastOnce())
            ->method('getActive')
            ->with($cartId, [])
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects(static::atLeastOnce())
            ->method('getItemById')
            ->with($itemId)
            ->willReturn($this->quoteItemMock);
        $this->giftMessageMock->expects(static::atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $this->dataHelperMock->expects(static::atLeastOnce())
            ->method('isGiftWrappingAvailableForItems')
            ->willReturn(false);
        $this->quoteItemMock->expects(static::never())
            ->method('save');

        $this->assertTrue($this->plugin->afterSave($this->subjectMock, true, 7, $this->giftMessageMock, 11));
    }
}
