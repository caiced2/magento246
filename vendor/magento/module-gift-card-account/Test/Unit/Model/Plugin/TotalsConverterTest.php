<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Model\Plugin;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftCardAccount\Model\Plugin\TotalsConverter as TotalsConverterPlugin;
use Magento\Quote\Api\Data\TotalSegmentExtensionFactory;
use Magento\Quote\Api\Data\TotalSegmentExtensionInterface;
use Magento\Quote\Api\Data\TotalSegmentInterface;
use Magento\Quote\Model\Cart\TotalsConverter as CartTotalsConverter;
use Magento\Quote\Model\Quote\Address\Total as AddressTotal;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TotalsConverterTest extends TestCase
{
    /**
     * @var TotalsConverterPlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var TotalSegmentExtensionFactory|MockObject
     */
    private $totalSegmentExtensionFactoryMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var CartTotalsConverter|MockObject
     */
    private $subjectMock;

    /**
     * @var TotalSegmentInterface|MockObject
     */
    private $totalSegmentMock;

    /**
     * @var AddressTotal|MockObject
     */
    private $addressTotalMock;

    /**
     * @var TotalSegmentExtensionInterface|MockObject
     */
    private $totalSegmentExtensionMock;

    protected function setUp(): void
    {
        $this->totalSegmentExtensionFactoryMock = $this->getMockBuilder(TotalSegmentExtensionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->getMockForAbstractClass();
        $this->subjectMock = $this->getMockBuilder(CartTotalsConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->totalSegmentMock = $this->getMockBuilder(TotalSegmentInterface::class)
            ->getMockForAbstractClass();
        $this->addressTotalMock = $this->getMockBuilder(AddressTotal::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGiftCards'])
            ->getMock();
        $this->totalSegmentExtensionMock = $this->getMockBuilder(TotalSegmentExtensionInterface::class)
            ->setMethods(['setGiftCards'])
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            TotalsConverterPlugin::class,
            [
                'totalSegmentExtensionFactory' => $this->totalSegmentExtensionFactoryMock,
                'serializer' => $this->serializerMock
            ]
        );
    }

    public function testAfterProcessNoAddressTotals()
    {
        $result = [];

        $this->totalSegmentExtensionFactoryMock->expects(static::never())
            ->method('create');

        $this->assertEquals($result, $this->plugin->afterProcess($this->subjectMock, $result, []));
    }

    public function testAfterProcess()
    {
        $result = ['giftcardaccount' => $this->totalSegmentMock];
        $giftCardsData = ['giftCard1' => 'giftCardData1', 'giftCard2' => 'giftCardData2'];
        $giftCardsJson = '{"giftCard1":"giftCardData1","giftCard2":"giftCardData2"}';

        $this->totalSegmentExtensionFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($this->totalSegmentExtensionMock);
        $this->addressTotalMock->expects(static::atLeastOnce())
            ->method('getGiftCards')
            ->willReturn($giftCardsData);
        $this->serializerMock->expects(static::any())
            ->method('serialize')
            ->willReturnMap(
                [
                    [$giftCardsData, $giftCardsJson]
                ]
            );
        $this->totalSegmentExtensionMock->expects(static::atLeastOnce())
            ->method('setGiftCards')
            ->with($giftCardsJson)
            ->willReturnSelf();
        $this->totalSegmentMock->expects(static::once())
            ->method('setExtensionAttributes')
            ->with($this->totalSegmentExtensionMock)
            ->willReturnSelf();

        $this->assertEquals(
            $result,
            $this->plugin->afterProcess($this->subjectMock, $result, ['giftcardaccount' => $this->addressTotalMock])
        );
    }
}
