<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Observer;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Helper\Data;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\GiftCardAccount\Model\GiftcardaccountFactory;
use Magento\GiftCardAccount\Observer\ProcessOrderPlace;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessOrderPlaceTest extends TestCase
{
    /** @var ProcessOrderPlace */
    private $model;

    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var DataObject
     */
    private $event;

    /**
     * Gift card account giftcardaccount
     *
     * @var GiftcardaccountFactory|MockObject
     */
    private $giftCAFactoryMock;

    /**
     * Gift card account data
     *
     * @var Data|MockObject
     */
    private $giftCAHelperMock = null;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->giftCAFactoryMock  = $this->getMockBuilder(GiftcardaccountFactory::class)
            ->setMethods(['create', 'load', 'charge', 'setOrder', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->giftCAHelperMock = $objectManagerHelper->getObject(
            Data::class,
            ['context' => $contextMock]
        );

        $this->model = $objectManagerHelper->getObject(
            ProcessOrderPlace::class,
            [
                'giftCAFactory' => $this->giftCAFactoryMock,
                'giftCAHelper' => $this->giftCAHelperMock,
            ]
        );

        $this->event = new DataObject();

        $this->observer = new Observer(['event' => $this->event]);
    }

    /**
     * @param array $giftCards
     * @param float|int $giftCardsAmount
     * @param float|int $baseGiftCardsAmount
     * @dataProvider processOrderPlaceDataProvider
     */
    public function testProcessOrderPlace($giftCards, $giftCardsAmount, $baseGiftCardsAmount)
    {
        $giftCardsQuote = is_array($giftCards) ? json_encode($giftCards) : $giftCards;
        $order = new DataObject();

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(
                [
                    'getShippingAddress',
                    'getBillingAddress',
                    'isVirtual',
                    'getGiftCardsAmount',
                    'getBaseGiftCardsAmount',
                    'getTotals'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock->expects($this->any())->method('isVirtual')->willReturn(false);
        $addressMock = $this->getMockBuilder(Address::class)
            ->addMethods(['getGiftCardsAmount', 'getBaseGiftCardsAmount', 'getGiftCards'])
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock->expects($this->any())->method('getGiftCards')->willReturn($giftCardsQuote);
        $addressMock->expects($this->any())
            ->method('getGiftCardsAmount')
            ->willReturn($giftCardsAmount);
        $addressMock->expects($this->any())
            ->method('getBaseGiftCardsAmount')
            ->willReturn($baseGiftCardsAmount);

        $this->giftCAFactoryMock->expects($this->any())
            ->method('create')->willReturnSelf();
        $this->giftCAFactoryMock->expects($this->any())
            ->method('load')->willReturnSelf();
        $this->giftCAFactoryMock->expects($this->any())
            ->method('charge')->willReturnSelf();
        $this->giftCAFactoryMock->expects($this->any())
            ->method('setOrder')->willReturnSelf();
        $this->giftCAFactoryMock->expects($this->any())
            ->method('save')->willReturnSelf();

        $this->event->setOrder($order);
        $this->event->setQuote($quoteMock);
        $this->event->setAddress($addressMock);
        $this->model->execute($this->observer);

        $this->assertEquals($giftCardsAmount, $order->getGiftCardsAmount());
        $this->assertEquals($baseGiftCardsAmount, $order->getBaseGiftCardsAmount());
    }

    /**
     * @case 1 POSITIVE we try to send array of giftCards data and baseGiftCardsAmount (integer)
     * @case 2 POSITIVE we try to send empty array of giftCards data and baseGiftCardsAmount (float)
     * @case 3 POSITIVE we try to send null  giftCards  and null baseGiftCardsAmount
     *
     * @return array
     */
    public function processOrderPlaceDataProvider()
    {
        return [
            [
                [
                    [
                        Giftcardaccount::ID => "5",
                        Giftcardaccount::CODE => '0AIMPAGVTAUQ',
                        Giftcardaccount::AMOUNT => 100,
                        Giftcardaccount::BASE_AMOUNT => "100.0000"
                    ],
                    [
                        Giftcardaccount::ID => "6",
                        Giftcardaccount::CODE => 'GVTAUQ0AIMPA',
                        Giftcardaccount::AMOUNT => 200,
                        Giftcardaccount::BASE_AMOUNT => "200.0000"
                    ],
                ],
                300,
                300
            ],
            [[], 0.5, 0.5],
            [null, null, null],
        ];
    }
}
