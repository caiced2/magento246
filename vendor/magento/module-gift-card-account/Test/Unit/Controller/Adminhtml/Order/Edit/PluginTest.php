<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Controller\Adminhtml\Order\Edit;

use Magento\Backend\Model\Session\Quote;
use Magento\CustomerBalance\Helper\Data;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Controller\Adminhtml\Order\Edit\Plugin;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\Sales\Controller\Adminhtml\Order\Edit\Index;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    /**
     * @var Plugin
     */
    protected $plugin;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManager;

    /**
     * @var Data|MockObject
     */
    protected $customerBalanceData;

    /**
     * @var Quote|MockObject
     */
    protected $sessionQuote;

    /**
     * @var \Magento\GiftCardAccount\Helper\Data|MockObject
     */
    protected $giftCardAccountData;

    protected function setUp(): void
    {
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->customerBalanceData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionQuote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->giftCardAccountData = $this->getMockBuilder(\Magento\GiftCardAccount\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->plugin = $objectManagerHelper->getObject(
            Plugin::class,
            [
                'sessionQuote' => $this->sessionQuote,
                'messageManager' => $this->messageManager,
                'customerBalanceData' => $this->customerBalanceData,
                'giftCardAccountData' => $this->giftCardAccountData
            ]
        );
    }

    protected function initMocksGiftCardAccountData($giftCards)
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionQuote->expects($this->atLeastOnce())
            ->method('getOrder')
            ->willReturn($orderMock);

        $this->giftCardAccountData->expects($this->atLeastOnce())
            ->method('getCards')
            ->with($orderMock)
            ->willReturn($giftCards);
    }

    public function testBeforeIndexActionWithoutGiftCards()
    {
        $this->initMocksGiftCardAccountData([]);

        $this->customerBalanceData->expects($this->never())->method('isEnabled');
        $this->messageManager->expects($this->never())->method('addNotice');
        $this->messageManager->expects($this->never())->method('addError');

        $controllerOrderEdit = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin->beforeExecute($controllerOrderEdit);
    }

    public function testBeforeIndexActionStoreCreditEnable()
    {
        $giftCards = [
            Giftcardaccount::BASE_AMOUNT => 50,
            Giftcardaccount::AMOUNT => 50,
            Giftcardaccount::CODE => 'someCode'
        ];
        $this->initMocksGiftCardAccountData($giftCards);

        $this->customerBalanceData->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->messageManager->expects($this->once())
            ->method('addNotice')
            ->with('We will refund the gift card amount to your customer’s store credit');
        $this->messageManager->expects($this->never())->method('addError');

        $controllerOrderEdit = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin->beforeExecute($controllerOrderEdit);
    }

    public function testBeforeIndexActionStoreCreditDisabled()
    {
        $giftCards = [
            Giftcardaccount::BASE_AMOUNT => 50,
            Giftcardaccount::AMOUNT => 50,
            Giftcardaccount::CODE => 'someCode'
        ];
        $this->initMocksGiftCardAccountData($giftCards);

        $this->customerBalanceData->expects($this->once())->method('isEnabled')->willReturn(false);
        $this->messageManager->expects($this->once())
            ->method('addNotice')
            ->with('We will refund the gift card amount to your customer’s store credit');
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('Please enable Store Credit to refund the gift card amount to your customer');

        $controllerOrderEdit = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin->beforeExecute($controllerOrderEdit);
    }
}
