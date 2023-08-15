<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Model;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Currency;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Model\EmailManagement;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\GiftCardAccount\Model\History;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailManagementTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var EmailManagement
     */
    private $model;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CurrencyInterface|MockObject
     */
    private $localeCurrency;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var TransportBuilder|MockObject
     */
    private $transportBuilder;

    /**
     * @var SenderResolverInterface|MockObject
     */
    private $senderResolver;

    /**
     * @var array
     */
    private $account = [
        'name' => 'recipient_name',
        'email' => 'recipient_email@magento.com',
        'website_id' => '1',
        'store' => '2',
        'store_name' => 'Store Name',
        'store_code' => 'store_code_1',
        'balance' => '10',
        'code' => 'GCCODE'
    ];

    /**
     * Initialize testable object
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->localeCurrency = $this->getMockBuilder(CurrencyInterface::class)
            ->getMockForAbstractClass();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMockForAbstractClass();
        $this->transportBuilder = $this->getMockBuilder(TransportBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->senderResolver = $this->getMockBuilder(SenderResolverInterface::class)
            ->getMockForAbstractClass();
        $this->model = $this->objectManager->getObject(
            EmailManagement::class,
            [
                'storeManager' => $this->storeManager,
                'localeCurrency' => $this->localeCurrency,
                'scopeConfig' => $this->scopeConfig,
                'transportBuilder' => $this->transportBuilder,
                'senderResolver' => $this->senderResolver
            ]
        );
    }

    /**
     * @dataProvider sendEmailDataProvider
     * @param bool $sendEmail
     */
    public function testSendEmail($sendEmail)
    {
        $currencyCode = 'USD';
        $giftcardAccount = $this->getMockBuilder(Giftcardaccount::class)
            ->setMethods(
                [
                    'getRecipientName',
                    'getRecipientEmail',
                    'getRecipientStore',
                    'getWebsiteId',
                    'getBalance',
                    'getCode',
                    'setHistoryAction',
                    'save'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $giftcardAccount->expects($this->any())->method('getRecipientName')->willReturn($this->account['name']);
        $giftcardAccount->expects($this->any())->method('getRecipientEmail')->willReturn($this->account['email']);
        $giftcardAccount->expects($this->any())->method('getRecipientStore')->willReturn($this->account['store']);
        $giftcardAccount->expects($this->any())->method('getWebsiteId')->willReturn($this->account['website_id']);
        $giftcardAccount->expects($this->any())->method('getBalance')->willReturn($this->account['balance']);
        $giftcardAccount->expects($this->any())->method('getCode')->willReturn($this->account['code']);
        $giftcardAccount->expects($this->any())->method('setHistoryAction')
            ->with(History::ACTION_SENT)
            ->willReturnSelf();
        $store = $this->getMockBuilder(StoreInterface::class)
            ->setMethods(['getBaseCurrencyCode'])
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->any())->method('getStore')->with($this->account['store'])
            ->willReturn($store);
        $store->expects($this->any())->method('getId')->willReturn($this->account['store']);
        $store->expects($this->any())->method('getBaseCurrencyCode')->willReturn($currencyCode);
        $store->expects($this->any())->method('getName')->willReturn($this->account['store_name']);
        $store->expects($this->any())->method('getCode')->willReturn($this->account['store_code']);
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeCurrency->expects($this->any())->method('getCurrency')->with($currencyCode)->willReturn($currency);
        $currency->expects($this->any())->method('toCurrency')->with($this->account['balance'])
            ->willReturn($this->account['balance']);
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturnMap(
            [
                [
                    'giftcard/giftcardaccount_email/template',
                    ScopeInterface::SCOPE_STORE,
                    $this->account['store'],
                    'scope\config\template'
                ],
                [
                    'giftcard/giftcardaccount_email/identity',
                    ScopeInterface::SCOPE_STORE,
                    $this->account['store'],
                    'giftcard/giftcardaccount_email/identity'
                ]
            ]
        );
        $this->transportBuilder->expects($this->any())->method('setTemplateIdentifier')->with('scope\config\template')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('setTemplateOptions')
            ->with(['area' => Area::AREA_FRONTEND, 'store' => $this->account['store']])
            ->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('setTemplateVars')
            ->with(
                [
                    'name' => $this->account['name'],
                    'code' => $this->account['code'],
                    'balance' => $this->account['balance'],
                    'store' => $store,
                    'store_name' => $this->account['store_name'],
                ]
            )->willReturnSelf();
        $this->senderResolver->expects($this->any())->method('resolve')->with(
            'giftcard/giftcardaccount_email/identity',
            $this->account['store_code']
        )->willReturn(['name' => 'Store Name', 'email' => 'store_email@magento.com']);
        $this->transportBuilder->expects($this->any())->method('setFrom')->with(
            ['name' => 'Store Name', 'email' => 'store_email@magento.com']
        )->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('addTo')
            ->with($this->account['email'], $this->account['name'])
            ->willReturnSelf();
        $transport = $this->getMockBuilder(TransportInterface::class)
            ->getMockForAbstractClass();
        $this->transportBuilder->expects($this->any())->method('getTransport')->willReturn($transport);
        if ($sendEmail) {
            $transport->expects($this->atLeastOnce())->method('sendMessage')->willReturnSelf();
            $giftcardAccount->expects($this->atLeastOnce())->method('setHistoryAction')
                ->with(History::ACTION_SENT)->willReturnSelf();
            $giftcardAccount->expects($this->atLeastOnce())->method('save')->willReturnSelf();
        } else {
            $transport->expects($this->atLeastOnce())->method('sendMessage')
                ->willThrowException(new MailException(__('test message')));
        }
        $this->assertEquals($sendEmail, $this->model->sendEmail($giftcardAccount));
    }

    /**
     * @return array
     */
    public function sendEmailDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }
}
