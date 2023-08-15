<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Model;

use Magento\Checkout\Model\Session;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface;
use Magento\GiftCardAccount\Model\GiftCardConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiftCardConfigProviderTest extends TestCase
{
    /**
     * @var GiftCardConfigProvider
     */
    protected $model;

    /**
     * @var MockObject|GiftCardAccountManagementInterface
     */
    protected $management;

    /**
     * @var MockObject|Session
     */
    protected $session;

    /**
     * Initialize testable object
     */
    protected function setUp(): void
    {
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->management = $this->getMockBuilder(
            GiftCardAccountManagementInterface::class
        )->getMock();

        $this->model = new GiftCardConfigProvider(
            $this->management,
            $this->session
        );
    }

    /**
     * @test
     */
    public function testGetConfig()
    {
        $quoteId = 'quoteId#1';
        $giftCards = ['giftCard1', 'giftCard1'];
        $amount = 12.34;
        $giftCard = $this->getMockBuilder(GiftCardAccountInterface::class)
            ->getMock();

        $this->session->expects($this->once())
            ->method('getQuoteId')
            ->willReturn($quoteId);
        $this->management->expects($this->once())
            ->method('getListByQuoteId')
            ->with($quoteId)
            ->willReturn($giftCard);
        $giftCard->expects($this->any())
            ->method('getGiftCards')
            ->willReturn($giftCards);
        $giftCard->expects($this->any())
            ->method('getGiftCardsAmountUsed')
            ->willReturn($amount);

        $this->assertEquals(
            [
                'payment' => [
                    'giftCardAccount' => [
                        'hasUsage' => true,
                        'amount'   => $amount,
                        'cards'    => $giftCards,
                        'available_amount' => null
                    ]
                ]
            ],
            $this->model->getConfig()
        );
    }
}
