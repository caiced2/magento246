<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Model\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftCard\Model\Giftcard;
use Magento\GiftCard\Model\Plugin\QuoteItem as QuoteItemPlugin;
use Magento\Quote\Model\Quote\Item\AbstractItem as AbstractQuoteItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteItemTest extends TestCase
{
    /**
     * @var QuoteItemPlugin
     */
    private $quoteItemPlugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var ToOrderItem|MockObject
     */
    private $toOrderItemMock;

    /**
     * @var OrderItem|MockObject
     */
    private $orderItemMock;

    /**
     * @var AbstractQuoteItem|MockObject
     */
    private $quoteItemMock;

    /**
     * @var Product|MockObject
     */
    private $productMock;

    /**
     * @var Store|MockObject
     */
    private $storeMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMockForAbstractClass();
        $this->toOrderItemMock = $this->getMockBuilder(ToOrderItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderItemMock = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemMock = $this->getMockBuilder(AbstractQuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMockForAbstractClass();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCustomOption',
                    'getUseConfigLifetime', 'getUseConfigIsRedeemable', 'getUseConfigEmailTemplate',
                    'getLifetime', 'getIsRedeemable', 'getEmailTemplate', 'getGiftcardType'
                ]
            )
            ->getMock();
        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteItemMock->expects(static::any())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->orderItemMock->expects(static::any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->quoteItemPlugin = $this->objectManagerHelper->getObject(
            QuoteItemPlugin::class,
            ['scopeConfig' => $this->scopeConfigMock]
        );
    }

    public function testAfterConvert()
    {
        $lifeTime = 'lifetime_value';
        $isRedeemable = '1';
        $emailTemplate = 'email_template_value';
        $giftCardType = 'giftcard_type_value';

        $this->commonLogic($lifeTime, $isRedeemable, $emailTemplate, $giftCardType);
        $this->getDataFromProduct($lifeTime, $isRedeemable, $emailTemplate);

        $this->assertSame(
            $this->orderItemMock,
            $this->quoteItemPlugin->afterConvert($this->toOrderItemMock, $this->orderItemMock, $this->quoteItemMock)
        );
    }

    public function testAfterConvertUseConfig()
    {
        $lifeTime = 'lifetime_value';
        $isRedeemable = '1';
        $emailTemplate = 'email_template_value';
        $giftCardType = 'giftcard_type_value';

        $this->commonLogic($lifeTime, $isRedeemable, $emailTemplate, $giftCardType, true);
        $this->getDataFromConfig($lifeTime, $isRedeemable, $emailTemplate);

        $this->assertSame(
            $this->orderItemMock,
            $this->quoteItemPlugin->afterConvert($this->toOrderItemMock, $this->orderItemMock, $this->quoteItemMock)
        );
    }

    /**
     * Set common expectations
     *
     * @param mixed $lifeTime
     * @param mixed $isRedeemable
     * @param mixed $emailTemplate
     * @param mixed $giftCardType
     * @param bool $useConfig
     * @return void
     */
    private function commonLogic(
        $lifeTime,
        $isRedeemable,
        $emailTemplate,
        $giftCardType,
        $useConfig = false
    ) {
        $initialProductOptions = [
            'option1' => 'value1',
            'option2' => 'value2'
        ];
        $customOptionsMap = [
            ['giftcard_sender_name', new DataObject(['value' => 'sender_name_value'])],
            ['giftcard_sender_email', new DataObject(['value' => 'sender_email_value'])],
            ['giftcard_recipient_name', new DataObject(['value' => 'recipient_name_value'])],
            ['giftcard_recipient_email', new DataObject(['value' => 'recipient_email_value'])],
            ['giftcard_message', new DataObject(['value' => 'message_value'])]
        ];
        $productOptions = [
            'option1' => 'value1',
            'option2' => 'value2',
            'giftcard_sender_name' => 'sender_name_value',
            'giftcard_sender_email' => 'sender_email_value',
            'giftcard_recipient_name' => 'recipient_name_value',
            'giftcard_recipient_email' => 'recipient_email_value',
            'giftcard_message' => 'message_value',
            'giftcard_lifetime' => $lifeTime,
            'giftcard_is_redeemable' => (int)$isRedeemable,
            'giftcard_email_template' => $emailTemplate,
            'giftcard_type' => $giftCardType
        ];

        $this->orderItemMock->expects(static::any())
            ->method('getProductOptions')
            ->willReturn($initialProductOptions);
        $this->productMock->expects(static::any())
            ->method('getCustomOption')
            ->willReturnMap($customOptionsMap);
        $this->productMock->expects(static::atLeastOnce())
            ->method('getUseConfigLifetime')
            ->willReturn($useConfig);
        $this->productMock->expects(static::atLeastOnce())
            ->method('getUseConfigIsRedeemable')
            ->willReturn($useConfig);
        $this->productMock->expects(static::atLeastOnce())
            ->method('getUseConfigEmailTemplate')
            ->willReturn($useConfig);
        $this->productMock->expects(static::any())
            ->method('getGiftcardType')
            ->willReturn($giftCardType);
        $this->orderItemMock->expects(static::once())
            ->method('setProductOptions')
            ->with($productOptions)
            ->willReturnSelf();
    }

    /**
     * Set expectations in case when data is taken from product
     *
     * @param mixed $lifeTime
     * @param mixed $isRedeemable
     * @param mixed $emailTemplate
     * @return void
     */
    private function getDataFromProduct($lifeTime, $isRedeemable, $emailTemplate)
    {
        $this->productMock->expects(static::atLeastOnce())
            ->method('getLifetime')
            ->willReturn($lifeTime);
        $this->productMock->expects(static::atLeastOnce())
            ->method('getIsRedeemable')
            ->willReturn($isRedeemable);
        $this->productMock->expects(static::atLeastOnce())
            ->method('getEmailTemplate')
            ->willReturn($emailTemplate);
        $this->scopeConfigMock->expects(static::never())
            ->method('getValue');
        $this->scopeConfigMock->expects(static::never())
            ->method('isSetFlag');
    }

    /**
     * Set expectations in case when data is taken from config
     *
     * @param mixed $lifeTime
     * @param mixed $isRedeemable
     * @param mixed $emailTemplate
     * @return void
     */
    private function getDataFromConfig($lifeTime, $isRedeemable, $emailTemplate)
    {
        $this->scopeConfigMock->expects(static::atLeastOnce())
            ->method('getValue')
            ->willReturnMap(
                [
                    [
                        Giftcard::XML_PATH_LIFETIME,
                        StoreScopeInterface::SCOPE_STORE,
                        $this->storeMock,
                        $lifeTime
                    ],
                    [
                        Giftcard::XML_PATH_EMAIL_TEMPLATE,
                        StoreScopeInterface::SCOPE_STORE,
                        $this->storeMock,
                        $emailTemplate
                    ]
                ]
            );
        $this->scopeConfigMock->expects(static::atLeastOnce())
            ->method('isSetFlag')
            ->with(Giftcard::XML_PATH_IS_REDEEMABLE, StoreScopeInterface::SCOPE_STORE, $this->storeMock)
            ->willReturn($isRedeemable);
        $this->productMock->expects(static::never())
            ->method('getLifetime');
        $this->productMock->expects(static::never())
            ->method('getIsRedeemable');
        $this->productMock->expects(static::never())
            ->method('getEmailTemplate');
    }
}
