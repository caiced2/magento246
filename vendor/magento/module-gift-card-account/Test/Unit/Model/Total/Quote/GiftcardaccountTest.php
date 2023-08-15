<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Model\Total\Quote;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\GiftCardAccount\Helper\Data;
use Magento\GiftCardAccount\Model\Giftcardaccount as GiftcardaccountModel;
use Magento\GiftCardAccount\Model\GiftcardaccountFactory;
use Magento\GiftCardAccount\Model\Total\Quote\Giftcardaccount;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiftcardaccountTest extends TestCase
{
    /**
     * @var Giftcardaccount
     */
    private $model;

    /**
     * @var Data|MockObject
     */
    private $giftCAHelperMock;

    /**
     * @var GiftcardaccountFactory|MockObject
     */
    private $giftCAFactory;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * Initialize testable object
     */
    protected function setUp(): void
    {
        $this->giftCAHelperMock = $this->createMock(Data::class);
        $this->giftCAFactory = $this->getMockBuilder(GiftcardaccountFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->priceCurrency = $this->getMockForAbstractClass(PriceCurrencyInterface::class);
        $this->model = new Giftcardaccount(
            $this->giftCAHelperMock,
            $this->giftCAFactory,
            $this->priceCurrency
        );
    }

    public function testFetch()
    {
        /** @var Quote|MockObject $quoteMock */
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getAddressesCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Total|MockObject $totalMock */
        $totalMock = $this->getMockBuilder(Total::class)
            ->setMethods(['getGiftCardsAmount'])
            ->disableOriginalConstructor()
            ->getMock();
        $card = [
            GiftcardaccountModel::ID => "7",
            GiftcardaccountModel::CODE => 'GHTRPAGVTAUQ',
            GiftcardaccountModel::AMOUNT => 50,
            GiftcardaccountModel::BASE_AMOUNT => "50.0000"
        ];
        $totalMock->expects($this->once())->method('getGiftCardsAmount')
            ->willReturn($card[GiftcardaccountModel::AMOUNT]);
        $this->giftCAHelperMock->expects($this->once())->method('getCards')->with($totalMock)->willReturn([$card]);
        $result = $this->model->fetch($quoteMock, $totalMock);
        $this->assertEquals(-$card[GiftcardaccountModel::AMOUNT], $result['value']);
    }
}
