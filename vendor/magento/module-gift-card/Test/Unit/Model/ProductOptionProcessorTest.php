<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductOptionExtensionInterface;
use Magento\Catalog\Api\Data\ProductOptionInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Magento\GiftCard\Api\Data\GiftCardOptionExtensionInterfaceFactory;
use Magento\GiftCard\Api\Data\GiftCardOptionInterface;
use Magento\GiftCard\Model\Giftcard\OptionFactory as GiftcardOptionFactory;
use Magento\GiftCard\Model\ProductOptionProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductOptionProcessorTest extends TestCase
{
    /**
     * @var ProductOptionProcessor
     */
    protected $processor;

    /**
     * @var DataObject|MockObject
     */
    protected $dataObject;

    /**
     * @var DataObjectFactory|MockObject
     */
    protected $dataObjectFactory;

    /**
     * @var DataObjectHelper|MockObject
     */
    protected $dataObjectHelper;

    /**
     * @var GiftcardOptionFactory|MockObject
     */
    protected $giftcardOptionFactory;

    /**
     * @var GiftCardOptionInterface|MockObject
     */
    protected $giftcardOption;

    /**
     * @var GiftCardOptionExtensionInterfaceFactory|MockObject
     */
    private $giftCardOptionExtensionFactory;

    protected function setUp(): void
    {
        $this->dataObject = $this->getMockBuilder(DataObject::class)
            ->setMethods(
                [
                    'getData',
                    'addData'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataObjectFactory = $this->getMockBuilder(\Magento\Framework\DataObject\Factory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->giftCardOptionExtensionFactory = $this->getMockBuilder(GiftCardOptionExtensionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataObjectFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->dataObject);

        $this->dataObjectHelper = $this->getMockBuilder(DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->giftcardOption = $this->getMockBuilder(
            GiftCardOptionInterface::class
        )
            ->setMethods([
                'getData',
            ])
            ->getMockForAbstractClass();

        $this->giftcardOptionFactory = $this->getMockBuilder(
            \Magento\GiftCard\Model\Giftcard\OptionFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->giftcardOptionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->giftcardOption);

        $this->processor = new ProductOptionProcessor(
            $this->dataObjectFactory,
            $this->dataObjectHelper,
            $this->giftcardOptionFactory,
            $this->giftCardOptionExtensionFactory
        );
    }

    /**
     * @param array|string $options
     * @param array $requestData
     * @dataProvider dataProviderConvertToBuyRequest
     */
    public function testConvertToBuyRequest(
        $options,
        $requestData
    ) {
        $productOptionMock = $this->getMockBuilder(ProductOptionInterface::class)
            ->getMockForAbstractClass();

        $productOptionExtensionMock = $this->getMockBuilder(
            ProductOptionExtensionInterface::class
        )
            ->setMethods([
                'getGiftcardItemOption',
            ])
            ->getMockForAbstractClass();

        $productOptionMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($productOptionExtensionMock);

        $productOptionExtensionMock->expects($this->any())
            ->method('getGiftcardItemOption')
            ->willReturn($this->giftcardOption);

        $this->giftcardOption->expects($this->any())
            ->method('getData')
            ->willReturn($options);

        $this->dataObject->expects($this->any())
            ->method('addData')
            ->with($requestData)
            ->willReturnSelf();

        $this->assertEquals($this->dataObject, $this->processor->convertToBuyRequest($productOptionMock));
    }

    /**
     * @return array
     */
    public function dataProviderConvertToBuyRequest()
    {
        return [
            [
                ['option'],
                ['option'],
            ],
            [[], []],
            ['', []],
        ];
    }

    /**
     * @param array|string $options
     * @param string|null $expected
     * @dataProvider dataProviderConvertToProductOption
     */
    public function testConvertToProductOption(
        $options,
        $expected
    ) {
        if (!empty($options) && is_array($options)) {
            $this->dataObject->expects($this->any())
                ->method('getData')
                ->willReturnMap([
                    ['giftcard_amount', null, $options['giftcard_amount']],
                    ['giftcard_sender_name', null, $options['giftcard_sender_name']],
                    ['giftcard_recipient_name', null, $options['giftcard_recipient_name']],
                    ['giftcard_sender_email', null, $options['giftcard_sender_email']],
                    ['giftcard_recipient_email', null, $options['giftcard_recipient_email']],
                    ['giftcard_message', null, $options['giftcard_message']],
                ]);
        } else {
            $this->dataObject->expects($this->any())
                ->method('getData')
                ->willReturnMap([
                    ['giftcard_amount', null, null],
                    ['giftcard_sender_name', null, null],
                    ['giftcard_recipient_name', null, null],
                    ['giftcard_sender_email', null, null],
                    ['giftcard_recipient_email', null, null],
                    ['giftcard_message', null, null],
                ]);
        }

        $this->dataObjectHelper->expects($this->any())
            ->method('populateWithArray')
            ->with(
                $this->giftcardOption,
                $options,
                GiftCardOptionInterface::class
            )
            ->willReturnSelf();

        $result = $this->processor->convertToProductOption($this->dataObject);

        if (!empty($expected)) {
            $this->assertArrayHasKey($expected, $result);
            $this->assertSame($this->giftcardOption, $result[$expected]);
        } else {
            $this->assertEmpty($result);
        }
    }

    /**
     * @return array
     */
    public function dataProviderConvertToProductOption()
    {
        return [
            [
                'options' => [
                    'giftcard_amount' => 1,
                    'giftcard_sender_name' => 'sender',
                    'giftcard_recipient_name' => 'recipient',
                    'giftcard_sender_email' => 'sender@example.com',
                    'giftcard_recipient_email' => 'recipient@example.com',
                    'giftcard_message' => 'message',
                ],
                'expected' => 'giftcard_item_option',
            ],
            [
                'options' => [],
                'expected' => null,
            ],
            [
                'options' => 'is not array',
                'expected' => null,
            ],
        ];
    }
}
