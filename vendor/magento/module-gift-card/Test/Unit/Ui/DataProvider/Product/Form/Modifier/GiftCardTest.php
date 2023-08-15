<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Test\Unit\Ui\DataProvider\Product\Form\Modifier\AbstractModifierTest;
use Magento\Config\Model\Config\Source\Email\Template as EmailTemplate;
use Magento\Config\Model\Config\Source\Email\TemplateFactory as EmailTemplateFactory;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as GiftCardProductType;
use Magento\GiftCard\Model\Giftcard as GiftCardModel;
use Magento\GiftCard\Ui\DataProvider\Product\Form\Modifier\GiftCard;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GiftCardTest extends AbstractModifierTest
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var Currency|MockObject
     */
    protected $currencyMock;

    /**
     * @var EmailTemplateFactory|MockObject
     */
    protected $emailTemplateFactoryMock;

    /**
     * @var EmailTemplate|MockObject
     */
    protected $emailTemplateMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMockForAbstractClass();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->setMethods(['getBaseCurrency'])
            ->getMockForAbstractClass();
        $this->currencyMock = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailTemplateFactoryMock = $this->getMockBuilder(EmailTemplateFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailTemplateMock = $this->getMockBuilder(EmailTemplate::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->arrayManagerMock = $this->getMockBuilder(ArrayManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * {@inheritdoc}
     */
    protected function createModel()
    {
        return $this->objectManager->getObject(GiftCard::class, [
            'locator' => $this->locatorMock,
            'arrayManager' => $this->arrayManagerMock,
            'scopeConfig' => $this->scopeConfigMock,
            'storeManager' => $this->storeManagerMock,
            'emailTemplateFactory' => $this->emailTemplateFactoryMock
        ]);
    }

    public function testModifyDataNotGiftCard()
    {
        $data = [];

        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('wrong_product_type');

        $this->assertSame($data, $this->getModel()->modifyData($data));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testModifyData()
    {
        $productId = 111;
        $data = [
            $productId => [
                GiftCard::DATA_SOURCE_DEFAULT => []
            ]
        ];
        $panelData = [
            'giftcard_amounts' => [['value' => '15.12'], ['value' => '1.34']],
            'is_redeemable' => 'is_redeemable_value',
            'use_config_is_redeemable' => false,
            'lifetime' => 'lifetime_value',
            'use_config_lifetime' => false,
            'allow_message' => 'allow_message_value',
            'use_config_allow_message' => true,
            'email_template' => 'email_template_value',
            'use_config_email_template' => true
        ];
        $result = [
            $productId => [
                GiftCard::DATA_SOURCE_DEFAULT => $panelData
            ]
        ];

        $this->locatorMock->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->productMock->expects($this->any())
            ->method('getId')
            ->willReturn($productId);
        $this->productMock->expects($this->any())
            ->method('getTypeId')
            ->willReturn(GiftCardProductType::TYPE_GIFTCARD);
        $this->productMock->expects($this->any())
            ->method('getData')
            ->willReturnMap(
                [
                    ['use_config_is_redeemable', false],
                    ['use_config_lifetime', false],
                    ['use_config_allow_message', true],
                    ['use_config_email_template', true],
                    ['is_redeemable', 'is_redeemable_value'],
                    ['lifetime', 'lifetime_value']
                ]
            );
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    [
                        GiftCardModel::XML_PATH . 'allow_message',
                        ScopeInterface::SCOPE_STORE,
                        null,
                        'allow_message_value'
                    ],
                    [
                        GiftCardModel::XML_PATH . 'email_template',
                        ScopeInterface::SCOPE_STORE,
                        null,
                        'email_template_value'
                    ]
                ]
            );
        $this->arrayManagerMock->expects($this->once())
            ->method('merge')
            ->with($productId . '/' . GiftCard::DATA_SOURCE_DEFAULT, $data, $panelData)
            ->willReturn($result);
        $this->arrayManagerMock->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    [
                        $productId . '/' . GiftCard::DATA_SOURCE_DEFAULT . '/' . GiftCard::FIELD_GIFTCARD_AMOUNTS,
                        $data,
                        [],
                        ArrayManager::DEFAULT_PATH_DELIMITER,
                        [['value' => 15.1235], ['value' => 1.3444]]
                    ],
                    [
                        $productId . '/' . GiftCard::DATA_SOURCE_DEFAULT . '/' . GiftCard::FIELD_OPEN_AMOUNT_MIN,
                        $data,
                        null,
                        ArrayManager::DEFAULT_PATH_DELIMITER,
                        0.1235
                    ],
                    [
                        $productId . '/' . GiftCard::DATA_SOURCE_DEFAULT . '/' . GiftCard::FIELD_OPEN_AMOUNT_MAX,
                        $data,
                        null,
                        ArrayManager::DEFAULT_PATH_DELIMITER,
                        4.9901
                    ]
                ]
            );

        $this->assertSame($result, $this->getModel()->modifyData($data));
    }

    public function testModifyMetaNotGiftCard()
    {
        $meta = [];

        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('wrong_product_type');

        $this->assertSame($meta, $this->getModel()->modifyMeta($meta));
    }

    public function testModifyMeta()
    {
        $this->productMock->expects($this->any())
            ->method('getTypeId')
            ->willReturn(GiftCardProductType::TYPE_GIFTCARD);
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())
            ->method('getBaseCurrency')
            ->willReturn($this->currencyMock);
        $this->storeManagerMock->expects($this->any())
            ->method('getWebsites')
            ->willReturn([]);
        $this->arrayManagerMock->expects($this->any())
            ->method('set')
            ->willReturn([GiftCard::GROUP_GIFTCARD => []]);
        $this->arrayManagerMock->expects($this->any())
            ->method('replace')
            ->willReturnArgument(1);
        $this->arrayManagerMock->expects($this->any())
            ->method('merge')
            ->willReturnArgument(1);
        $this->arrayManagerMock->expects($this->any())
            ->method('remove')
            ->willReturnArgument(1);
        $this->emailTemplateFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->emailTemplateMock);

        $this->assertArrayHasKey(GiftCard::GROUP_GIFTCARD, $this->getModel()->modifyMeta([]));
    }
}
