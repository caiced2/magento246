<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Model\Quote\Item;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject\Factory;
use Magento\GiftCard\Model\Giftcard\Option;
use Magento\GiftCard\Model\Giftcard\OptionFactory;
use Magento\GiftCard\Model\Quote\Item\CartItemProcessor;
use Magento\Quote\Api\Data\ProductOptionExtension;
use Magento\Quote\Api\Data\ProductOptionExtensionFactory;
use Magento\Quote\Api\Data\ProductOptionInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\ProductOptionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CartItemProcessorTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $objectFactoryMock;

    /**
     * @var MockObject
     */
    protected $dataObjHelperMock;

    /**
     * @var MockObject
     */
    protected $gcFactoryMock;

    /**
     * @var MockObject
     */
    protected $prodOptFactoryMock;

    /**
     * @var MockObject
     */
    protected $extFactoryMock;

    /**
     * @var MockObject
     */
    protected $cartItemMock;

    /**
     * @var MockObject
     */
    protected $extensionAttributeMock;

    /**
     * @var MockObject
     */
    protected $productOptionMock;

    /**
     * @var MockObject
     */
    protected $giftCardOptionMock;

    /**
     * @var MockObject
     */
    protected $optionMock;

    /**
     * @var CartItemProcessor
     */
    protected $model;

    protected function setUp(): void
    {
        $this->objectFactoryMock =
            $this->createPartialMock(Factory::class, ['create']);
        $this->dataObjHelperMock = $this->createMock(DataObjectHelper::class);
        $this->gcFactoryMock =
            $this->createPartialMock(OptionFactory::class, ['create']);
        $this->prodOptFactoryMock =
            $this->createPartialMock(ProductOptionFactory::class, ['create']);
        $this->extFactoryMock =
            $this->createPartialMock(ProductOptionExtensionFactory::class, ['create']);

        $this->extensionAttributeMock =
            $this->getMockBuilder(ProductOptionExtension::class)
                ->addMethods(['getGiftcardItemOption', 'setGiftcardItemOption'])
                ->getMock();
        $this->productOptionMock = $this->getMockForAbstractClass(ProductOptionInterface::class);
        $this->giftCardOptionMock = $this->createMock(Option::class);
        $this->cartItemMock = $this->createMock(Item::class);
        $this->optionMock =
            $this->getMockBuilder(\Magento\Quote\Model\Quote\Item\Option::class)->addMethods(['getCode'])
                ->onlyMethods(['getValue'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->model = new CartItemProcessor(
            $this->objectFactoryMock,
            $this->dataObjHelperMock,
            $this->gcFactoryMock,
            $this->prodOptFactoryMock,
            $this->extFactoryMock
        );
    }

    public function testConvertToBuyRequest()
    {
        $requestData = [
            "giftcard_amount" => "custom",
            "custom_giftcard_amount" => 7,
        ];
        $this->cartItemMock
            ->expects($this->once())
            ->method('getProductOption')
            ->willReturn($this->productOptionMock);
        $this->productOptionMock
            ->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->extensionAttributeMock
            ->expects($this->any())
            ->method('getGiftcardItemOption')
            ->willReturn($this->giftCardOptionMock);
        $this->giftCardOptionMock->expects($this->once())->method('getData')->willReturn($requestData);
        $this->objectFactoryMock->expects($this->once())->method('create')->with($requestData);
        $this->model->convertToBuyRequest($this->cartItemMock);
    }

    public function testConvertToBuyRequestWhenProductOptionNotExist()
    {
        $this->cartItemMock
            ->expects($this->once())
            ->method('getProductOption')
            ->willReturn(null);
        $this->objectFactoryMock->expects($this->never())->method('create');
        $this->model->convertToBuyRequest($this->cartItemMock);
    }

    public function testProcessProductOptions()
    {
        $this->cartItemMock->expects($this->once())->method('getOptions')->willReturn([$this->optionMock]);
        $this->optionMock->expects($this->once())->method('getCode')->willReturn('giftcard_amount');
        $this->optionMock->expects($this->once())->method('getValue')->willReturn(10);
        $this->gcFactoryMock->expects($this->once())->method('create')->willReturn($this->giftCardOptionMock);
        $this->dataObjHelperMock
            ->expects($this->once())
            ->method('populateWithArray')
            ->with($this->giftCardOptionMock, ['giftcard_amount'=> 10])
            ->willReturn($this->giftCardOptionMock);
        $this->cartItemMock
            ->expects($this->exactly(2))
            ->method('getProductOption')
            ->willReturn($this->productOptionMock);
        $this->productOptionMock
            ->expects($this->exactly(2))
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->extensionAttributeMock
            ->expects($this->once())
            ->method('setGiftcardItemOption')
            ->with($this->giftCardOptionMock);
        $this->productOptionMock
            ->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock);
        $this->cartItemMock->expects($this->once())->method('setProductOption')->with($this->productOptionMock);

        $this->assertEquals($this->cartItemMock, $this->model->processOptions($this->cartItemMock));
    }

    public function testProcessProductOptionsWhenExtensibleAttributeNotExist()
    {
        $this->cartItemMock->expects($this->once())->method('getOptions')->willReturn([$this->optionMock]);
        $this->optionMock->expects($this->once())->method('getCode')->willReturn('giftcard_amount');
        $this->optionMock->expects($this->once())->method('getValue')->willReturn(10);
        $this->gcFactoryMock->expects($this->once())->method('create')->willReturn($this->giftCardOptionMock);
        $this->dataObjHelperMock
            ->expects($this->once())
            ->method('populateWithArray')
            ->with($this->giftCardOptionMock, ['giftcard_amount'=> 10])
            ->willReturn($this->giftCardOptionMock);
        $this->cartItemMock
            ->expects($this->once())
            ->method('getProductOption')
            ->willReturn(null);
        $this->prodOptFactoryMock->expects($this->once())->method('create')->willReturn($this->productOptionMock);
        $this->productOptionMock
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $this->extFactoryMock->expects($this->once())->method('create')->willReturn($this->extensionAttributeMock);
        $this->extensionAttributeMock
            ->expects($this->once())
            ->method('setGiftcardItemOption')
            ->with($this->giftCardOptionMock);
        $this->productOptionMock
            ->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock);
        $this->cartItemMock->expects($this->once())->method('setProductOption')->with($this->productOptionMock);
        $this->assertEquals($this->cartItemMock, $this->model->processOptions($this->cartItemMock));
    }

    public function testProcessProductOptionsWhenOptionsNotExists()
    {
        $this->cartItemMock->expects($this->once())->method('getOptions')->willReturn(null);
        $this->dataObjHelperMock
            ->expects($this->never())
            ->method('populateWithArray');
        $this->assertEquals($this->cartItemMock, $this->model->processOptions($this->cartItemMock));
    }
}
