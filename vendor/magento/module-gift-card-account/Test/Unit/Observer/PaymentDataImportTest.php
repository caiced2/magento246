<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\GiftCardAccount\Helper\Data;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\GiftCardAccount\Model\GiftcardaccountFactory;
use Magento\GiftCardAccount\Observer\PaymentDataImport;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentDataImportTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $giftCardAccountHelper;

    /**
     * @var GiftcardaccountFactory|MockObject
     */
    private $getCardAccountFactory;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var PaymentDataImport
     */
    private $paymentDataImport;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->giftCardAccountHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->getCardAccountFactory = $this->getMockBuilder(
            GiftcardaccountFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->paymentDataImport = new PaymentDataImport(
            $this->giftCardAccountHelper,
            $this->getCardAccountFactory,
            $this->storeManager
        );
    }

    /**
     * Test case when event object has no quote object
     */
    public function testExecuteWithNoQuote()
    {
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getQuote')
            ->willReturn(null);

        $this->giftCardAccountHelper->expects($this->never())
            ->method('getCards')
            ->with($quoteMock);

        $observerMock = $this->getObserverMock($paymentMock);
        $this->paymentDataImport->execute($observerMock);
    }

    /**
     * Test case when quote object has no customer ID
     */
    public function testExecuteWithNoCustomerId()
    {
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId'])
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(null);

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->giftCardAccountHelper->expects($this->never())
            ->method('getCards')
            ->with($quoteMock);

        $observerMock = $this->getObserverMock($paymentMock);
        $this->paymentDataImport->execute($observerMock);
    }

    /**
     * Test case when quote has no Gift Card applied
     */
    public function testExecuteWithNoGiftCards()
    {
        $customerId = 1;
        $storeId = 1;

        $baseGiftCardsAmountUsed = 0;

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCustomerId',
                'getStoreId',
                'getBaseGiftCardsAmountUsed',
            ])
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $quoteMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
        $quoteMock->expects($this->once())
            ->method('getBaseGiftCardsAmountUsed')
            ->willReturn($baseGiftCardsAmountUsed);

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $this->giftCardAccountHelper->expects($this->exactly(2))
            ->method('getCards')
            ->with($quoteMock)
            ->willReturn([]);

        $observerMock = $this->getObserverMock($paymentMock);
        $this->paymentDataImport->execute($observerMock);
    }

    /**
     * Test case with Gift Cards that have 'Available' state
     */
    public function testExecuteAvailableGiftCards()
    {
        $customerId = 1;
        $storeId = 1;

        $giftCardCode = 'gift_card_code';
        $baseGiftCardsAmountUsed = 0;

        $giftCards = [
            [
                Giftcardaccount::CODE => $giftCardCode,
            ],
        ];

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCustomerId',
                'getStoreId',
                'getBaseGiftCardsAmountUsed',
            ])
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $quoteMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
        $quoteMock->expects($this->once())
            ->method('getBaseGiftCardsAmountUsed')
            ->willReturn($baseGiftCardsAmountUsed);

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $this->giftCardAccountHelper->expects($this->exactly(2))
            ->method('getCards')
            ->with($quoteMock)
            ->willReturn($giftCards);

        $gitfCardAccountMock = $this->getMockBuilder(Giftcardaccount::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'loadByCode',
                'isValid',
                'getState',
            ])
            ->getMock();
        $gitfCardAccountMock->expects($this->exactly(2))
            ->method('loadByCode')
            ->with($giftCardCode)
            ->willReturnSelf();
        $gitfCardAccountMock->expects($this->once())
            ->method('isValid')
            ->with(true, true, $websiteMock)
            ->willReturn(true);
        $gitfCardAccountMock->expects($this->once())
            ->method('getState')
            ->willReturn(Giftcardaccount::STATE_AVAILABLE);

        $this->getCardAccountFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($gitfCardAccountMock);

        $observerMock = $this->getObserverMock($paymentMock);
        $this->paymentDataImport->execute($observerMock);
    }

    /**
     * Test case with Gift Cards that have 'Used' state
     */
    public function testExecuteUsedGiftCards()
    {
        $customerId = 1;
        $storeId = 1;

        $giftCardCode = 'gift_card_code';
        $baseGiftCardsAmountUsed = 0;

        $giftCards = [
            [
                Giftcardaccount::CODE => $giftCardCode,
            ],
        ];

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCustomerId',
                'getStoreId',
                'getBaseGiftCardsAmountUsed',
            ])
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $quoteMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
        $quoteMock->expects($this->once())
            ->method('getBaseGiftCardsAmountUsed')
            ->willReturn($baseGiftCardsAmountUsed);

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $this->giftCardAccountHelper->expects($this->exactly(2))
            ->method('getCards')
            ->with($quoteMock)
            ->willReturn($giftCards);

        $gitfCardAccountMock = $this->getMockBuilder(Giftcardaccount::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'loadByCode',
                'isValid',
                'getState',
                'removeFromCart',
            ])
            ->getMock();
        $gitfCardAccountMock->expects($this->exactly(2))
            ->method('loadByCode')
            ->with($giftCardCode)
            ->willReturnSelf();
        $gitfCardAccountMock->expects($this->once())
            ->method('isValid')
            ->with(true, true, $websiteMock)
            ->willReturn(true);
        $gitfCardAccountMock->expects($this->once())
            ->method('getState')
            ->willReturn(Giftcardaccount::STATE_USED);
        $gitfCardAccountMock->expects($this->once())
            ->method('removeFromCart')
            ->with(true, $quoteMock)
            ->willReturnSelf();

        $this->getCardAccountFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($gitfCardAccountMock);

        $observerMock = $this->getObserverMock($paymentMock);
        $this->paymentDataImport->execute($observerMock);
    }

    /**
     * Helper method to create Observer mock object
     *
     * @param MockObject $paymentMock
     * @return MockObject
     */
    private function getObserverMock($paymentMock)
    {
        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayment'])
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        return $observerMock;
    }
}
