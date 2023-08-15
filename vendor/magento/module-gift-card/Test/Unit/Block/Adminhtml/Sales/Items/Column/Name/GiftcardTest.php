<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Block\Adminhtml\Sales\Items\Column\Name;

use Magento\Framework\Escaper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCard\Block\Adminhtml\Sales\Items\Column\Name\Giftcard;
use Magento\Sales\Model\Order\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiftcardTest extends TestCase
{
    /**
     * @var Giftcard
     */
    protected $block;

    /**
     * @var Escaper|MockObject
     */
    protected $escaper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->escaper = $this->getMockBuilder(Escaper::class)
            ->onlyMethods(['escapeHtml'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->block = $objectManagerHelper->getObject(
            Giftcard::class,
            [
                'escaper' => $this->escaper
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetOrderOptions(): void
    {
        $expectedResult = [
            [
                'label' => 'Gift Card Type',
                'value' => 'Physical'
            ],
            [
                'label' => 'Gift Card Sender',
                'value' => 'sender_name &lt;sender_email&gt;',
                'custom_view' => true
            ],
            [
                'label' => 'Gift Card Recipient',
                'value' => 'recipient_name &lt;recipient_email&gt;',
                'custom_view' => true
            ],
            [
                'label' => 'Gift Card Message',
                'value' => 'giftcard_message'
            ],
            [
                'label' => 'Gift Card Lifetime',
                'value' => 'lifetime days'
            ],
            [
                'label' => 'Gift Card Is Redeemable',
                'value' => 'Yes'
            ],
            [
                'label' => 'Gift Card Accounts',
                'value' => 'xxx123<br />yyy456<br />N/A<br />N/A<br />N/A',
                'custom_view' => true
            ]
        ];

        $itemMock = $this->getMockBuilder(Item::class)
            ->onlyMethods(['getProductOptionByCode', 'getQtyOrdered'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->prepareCustomOptionMocks(
            $itemMock,
            ['giftcard_type', 1, false],
            ['giftcard_sender_name', 'sender_name', true],
            ['giftcard_sender_email', 'sender_email', true],
            ['giftcard_recipient_name', 'recipient_name', true],
            ['giftcard_recipient_email', 'recipient_email', true],
            ['giftcard_message', 'giftcard_message', true],
            ['giftcard_lifetime', 'lifetime', true],
            ['giftcard_is_redeemable', 1, true],
            ['giftcard_created_codes', ['xxx123', 'yyy456'], false]
        );

        $itemMock->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn(5);

        $this->assertEquals($expectedResult, $this->block->getOrderOptions());
    }

    /**
     * Prepare custom options mocks for testing.
     *
     * @param MockObject $itemMock
     * @param array $data in the next format: [$code, $result, $isEscaped]
     *
     * @return void
     */
    private function prepareCustomOptionMocks(MockObject $itemMock, array ...$data): void
    {
        $this->block->setData('item', $itemMock);
        $itemWithArgs = $itemWillReturnArgs = [];
        $escaperWithArgs = $escaperWillReturnArgs = [];

        foreach ($data as $datum) {
            list($code, $result, $isEscaped) = $datum;

            $itemWithArgs[] = [$code];

            if ($isEscaped) {
                $itemWillReturnArgs[] = 'some_option';
                $escaperWithArgs[] = ['some_option'];
                $escaperWillReturnArgs[] = $result;
            } else {
                $itemWillReturnArgs[] = $result;
            }
        }

        $itemMock
            ->method('getProductOptionByCode')
            ->withConsecutive(...$itemWithArgs)
            ->willReturnOnConsecutiveCalls(...$itemWillReturnArgs);

        $this->escaper
            ->method('escapeHtml')
            ->withConsecutive(...$escaperWithArgs)
            ->willReturnOnConsecutiveCalls(...$escaperWillReturnArgs);
    }
}
