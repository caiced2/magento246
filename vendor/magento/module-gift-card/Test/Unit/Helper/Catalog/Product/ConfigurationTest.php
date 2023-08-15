<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Helper\Catalog\Product;

use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Escaper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftCard\Helper\Catalog\Product\Configuration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests of helper for fetching properties by product configuration item.
 */
class ConfigurationTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Configuration
     */
    protected $helper;

    /**
     * @var Configuration|MockObject
     */
    protected $ctlgProdConfigur;

    /**
     * @var Escaper|MockObject
     */
    protected $escaper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->ctlgProdConfigur = $this->getMockBuilder(\Magento\Catalog\Helper\Product\Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->escaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->helper = $this->objectManagerHelper->getObject(
            Configuration::class,
            [
                'context' => $context,
                'ctlgProdConfigur' => $this->ctlgProdConfigur,
                'escaper' => $this->escaper
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetGiftcardOptions(): void
    {
        $expected = [
            [
                'label' => 'Gift Card Sender',
                'value' => 'sender_name &lt;sender@test.com&gt;',
                'option_type' => 'html'
            ],
            [
                'label' => 'Gift Card Recipient',
                'value' => 'recipient_name &lt;recipient@test.com&gt;',
                'option_type' => 'html'
            ],
            [
                'label' => 'Gift Card Message',
                'value' => 'some message',
                'option_type' => 'html'
            ]
        ];

        $itemMock = $this->getMockBuilder(ItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->prepareCustomOptions(
            $itemMock,
            ['giftcard_sender_name', 'sender_name', 'sender_name'],
            ['giftcard_sender_email', 'sender_email', 'sender@test.com'],
            ['giftcard_recipient_name', 'recipient_name', 'recipient_name'],
            ['giftcard_recipient_email', 'recipient_email', 'recipient@test.com'],
            ['giftcard_message', 'giftcard_message', 'some message']
        );

        $this->assertEquals($expected, $this->helper->getGiftcardOptions($itemMock));
    }

    /**
     * @param MockObject $itemMock
     * @param array $data in the next format: [$code, $value, $result]
     *
     * @return void
     */
    private function prepareCustomOptions(MockObject $itemMock, array ...$data): void
    {
        $itemMockWithArgs = $itemMockWillReturnArgs = [];
        $escaperWithArgs = $escaperWillReturnArgs = [];

        foreach ($data as $datum) {
            list ($code, $value, $result) = $datum;

            $optionMock = $this->getMockBuilder(OptionInterface::class)
                ->disableOriginalConstructor()
                ->getMock();
            $optionMock->expects($this->once())
                ->method('getValue')
                ->willReturn($value);

            $itemMockWithArgs[] = [$code];
            $itemMockWillReturnArgs[] = $optionMock;
            $escaperWithArgs[] = [$value];
            $escaperWillReturnArgs[] = $result;
        }
        $itemMock
            ->method('getOptionByCode')
            ->withConsecutive(...$itemMockWithArgs)
            ->willReturnOnConsecutiveCalls(...$itemMockWillReturnArgs);
        $this->escaper
            ->method('escapeHtml')
            ->withConsecutive(...$escaperWithArgs)
            ->willReturnOnConsecutiveCalls(...$escaperWillReturnArgs);
    }

    /**
     * @return void
     */
    public function testPrepareCustomOptionWithoutValue(): void
    {
        $code = 'option_code';

        $itemMock = $this->getMockBuilder(ItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $optionMock = $this->getMockBuilder(OptionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock->expects($this->once())
            ->method('getOptionByCode')
            ->with($code)
            ->willReturn($optionMock);
        $optionMock->expects($this->once())
            ->method('getValue')
            ->willReturn(null);

        $this->assertFalse($this->helper->prepareCustomOption($itemMock, $code));
    }
}
