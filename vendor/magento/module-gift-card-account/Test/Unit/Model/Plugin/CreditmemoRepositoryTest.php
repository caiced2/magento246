<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Model\Plugin;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountSearchResultInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\GiftCardAccount\Model\Plugin\CreditmemoRepository;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoExtension;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Api\Data\CreditmemoExtensionInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Creditmemo repository plugin.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreditmemoRepositoryTest extends TestCase
{
    /**
     * @var CreditmemoRepository
     */
    private $plugin;

    /**
     * @var CreditmemoRepositoryInterface|MockObject
     */
    private $subjectMock;

    /**
     * @var CreditmemoInterface|MockObject
     */
    private $creditmemoMock;

    /**
     * @var float
     */
    private $giftCardsAmount = 10;

    /**
     * @var float
     */
    private $baseGiftCardsAmount = 15;

    /**
     * @var CreditmemoExtension|MockObject
     */
    private $extensionAttributeMock;

    /**
     * @var CreditmemoSearchResultInterface|MockObject
     */
    private $creditmemoSearchResultMock;

    /**
     * @var CreditmemoExtensionFactory|MockObject
     */
    private $creditmemoExtensionFactoryMock;

    /**
     * @var GiftCardAccountRepositoryInterface|MockObject
     */
    private $giftCardAccountRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $criteriaBuilderMock;

    /** @var OrderRepositoryInterface|MockObject */
    private $orderRepositoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockForAbstractClass(
            CreditmemoRepositoryInterface::class
        );

        $this->creditmemoMock = $this->getMockBuilder(CreditmemoInterface::class)
            ->setMethods(
                [
                    'getExtensionAttributes',
                    'setExtensionAttributes',
                    'setGiftCardsAmount',
                    'setBaseGiftCardsAmount',
                    'getBaseGiftCardsAmount',
                    'getGiftCardsAmount',
                    'getOrderItemId',
                    'getQty',
                    'getOrder',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->extensionAttributeMock = $this->getMockBuilder(CreditmemoExtensionInterface::class)
            ->setMethods(
                [
                    'getGiftCardsAmount',
                    'getBaseGiftCardsAmount',
                    'setGiftCardsAmount',
                    'setBaseGiftCardsAmount',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->creditmemoSearchResultMock = $this->getMockForAbstractClass(
            CreditmemoSearchResultInterface::class
        );

        $this->creditmemoExtensionFactoryMock = $this->getMockBuilder(CreditmemoExtensionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->giftCardAccountRepositoryMock = $this->getMockBuilder(GiftCardAccountRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getList',
                    'getItems',
                ]
            )->getMockForAbstractClass();

        $searchCriteriaInterfaceMock = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->criteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'addFilter',
                    'setPageSize',
                    'create',
                ]
            )->getMock();
        $this->criteriaBuilderMock->method('addFilter')->willReturnSelf();
        $this->criteriaBuilderMock->method('setPageSize')->willReturnSelf();
        $this->criteriaBuilderMock->method('create')->willReturn($searchCriteriaInterfaceMock);

        $searchCriteriaInterfaceMock = $this->getMockBuilder(GiftCardAccountSearchResultInterface::class)
            ->disableOriginalConstructor(
                [
                    'getItems',
                ]
            )
            ->getMockForAbstractClass();
        $searchCriteriaInterfaceMock->method('getItems')->willReturn([]);

        $this->giftCardAccountRepositoryMock->method('getList')->willReturn($searchCriteriaInterfaceMock);

        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->plugin = new CreditmemoRepository(
            $this->creditmemoExtensionFactoryMock,
            $this->giftCardAccountRepositoryMock,
            $this->criteriaBuilderMock,
            $this->orderRepositoryMock
        );
    }

    public function testAfterGet()
    {
        $this->creditmemoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->creditmemoMock->expects($this->once())
            ->method('getGiftCardsAmount')
            ->willReturn($this->giftCardsAmount);
        $this->creditmemoMock->expects($this->once())
            ->method('getBaseGiftCardsAmount')
            ->willReturn($this->baseGiftCardsAmount);

        $this->extensionAttributeMock->expects($this->once())
            ->method('setGiftCardsAmount')
            ->with($this->giftCardsAmount)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects($this->once())
            ->method('setBaseGiftCardsAmount')
            ->with($this->baseGiftCardsAmount)
            ->willReturnSelf();

        $this->creditmemoMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGet($this->subjectMock, $this->creditmemoMock);
    }

    public function testAfterGetList()
    {
        $this->creditmemoSearchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->creditmemoMock]);

        $this->creditmemoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->creditmemoMock->expects($this->once())
            ->method('getGiftCardsAmount')
            ->willReturn($this->giftCardsAmount);
        $this->creditmemoMock->expects($this->once())
            ->method('getBaseGiftCardsAmount')
            ->willReturn($this->baseGiftCardsAmount);

        $this->extensionAttributeMock->expects($this->once())
            ->method('setGiftCardsAmount')
            ->with($this->giftCardsAmount)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects($this->once())
            ->method('setBaseGiftCardsAmount')
            ->with($this->baseGiftCardsAmount)
            ->willReturnSelf();

        $this->creditmemoMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGetList($this->subjectMock, $this->creditmemoSearchResultMock);
    }

    /**
     * Test plugin gift card account after save credit memo
     *
     * @return void
     */
    public function testAfterSave(): void
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->setMethods(['getItems', 'getIterator'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderItemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getItemId',
                    'getProductOptionByCode',
                ]
            )
            ->getMock();
        $orderItemMock->method('getItemId')
            ->willReturn(1);
        $orderItemMock->expects($this->any())
            ->method('getProductOptionByCode')
            ->with('giftcard_created_codes')
            ->willReturn(
                [
                    'testcode',
                ]
            );
        $iteratorOrder = new \ArrayIterator([$orderItemMock]);
        $orderMock->method('getItems')
            ->willReturn($iteratorOrder);

        $this->creditmemoMock->method('getOrderItemId')
            ->willReturn(1);
        $this->creditmemoMock->method('getQty')
            ->willReturn(1);

        $iteratorCredit = new \ArrayIterator([$this->creditmemoMock]);
        $this->creditmemoMock->method('getItems')
            ->willReturn($iteratorCredit);

        $this->creditmemoMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $this->plugin->afterSave($this->subjectMock, $this->creditmemoMock);
    }
}
