<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Helper;

use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Helper data test.
 */
class DataTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $objectManager;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var DataObject
     */
    private $dataObject;

    /**
     * Initialize testable object
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->dataObject = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getGiftCards'])
            ->getMock();
        $serializer = $this->getMockBuilder(Json::class)
            ->setMethods(['serialize', 'unserialize'])
            ->getMock();
        $serializer->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );
        $serializer->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->helper = $this->objectManager->getObject(
            Data::class,
            [
                'serializer' => $serializer,
            ]
        );
    }

    /**
     * @covers       \Magento\GiftCardAccount\Helper\Data::getCards()
     * @dataProvider getCardsDataProvider
     * @param mixed $value
     * @param mixed $expected
     */
    public function testGetCards($value, $expected)
    {
        $this->dataObject->expects($this->once())
            ->method('getGiftCards')
            ->willReturn($value);

        $this->assertSame($expected, $this->helper->getCards($this->dataObject));
    }

    /**
     * @covers \Magento\GiftCardAccount\Helper\Data::setCards()
     * @dataProvider setCardsDataProvider
     * @param mixed $value
     * @param mixed $expected
     */
    public function testSetCards($value, $expected)
    {
        $this->helper->setCards($this->dataObject, $value);

        $this->assertSame($expected, $this->dataObject->getData('gift_cards'));
    }

    /**
     * @return array
     */
    public function setCardsDataProvider()
    {
        return [
            // Variation 1
            [
                [1, 2, 3.0003],
                "[1,2,3.0003]"
            ],
            // Variation 2
            [
                [null],
                "[null]"
            ],
            // Variation 3
            [
                "text",
                "\"text\""
            ],
            // Variation 4
            [
                -999.99,
                "-999.99"
            ],
        ];
    }

    /**
     * @return array
     */
    public function getCardsDataProvider()
    {
        return [
            // Variation 1
            [
                '[1,2,3]',
                [1, 2, 3]
            ],
            // Variation 2
            [
                '{"key":[1,2,3.0003,null]}',
                ["key" => [1, 2, 3.0003, null]]
            ],
            // Variation 3
            [
                '{"key":["text"]}',
                ["key" => ["text"]]
            ],
            // Variation 4
            [
                '{}',
                []
            ],
            // Variation 5
            [
                null,
                []
            ],
        ];
    }
}
