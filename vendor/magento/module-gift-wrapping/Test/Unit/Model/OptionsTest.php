<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Model\Options;
use PHPUnit\Framework\TestCase;

/**
 * Gift wrapping options model's test.
 *
 * @deprecated Currently Options class doesn't used, will be removed in the nearest backward incompatible release.
 */
class OptionsTest extends TestCase
{
    /**
     * @var Options
     */
    protected $subject;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $serializerMock = $this->getMockBuilder(Json::class)
            ->setMethods(['serialize', 'unserialize'])
            ->getMock();
        $serializerMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(function ($parameter) {
                return json_encode($parameter);
            });
        $serializerMock->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(function ($parameter) {
                return json_decode($parameter, true);
            });
        $this->subject = $objectManagerHelper->getObject(
            Options::class,
            [
                'serializer' => $serializerMock,
            ]
        );
    }

    public function testSetDataObjectIfItemNotMagentoObject()
    {
        $itemMock = $this->createMock(\stdClass::class);
        $this->assertEquals($this->subject, $this->subject->setDataObject($itemMock));
    }

    public function testSetDataObjectIfItemHasNotWrappingOptions()
    {
        $itemMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getGiftwrappingOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->expects($this->once())->method('getGiftwrappingOptions')->willReturn(null);
        $this->assertEquals($this->subject, $this->subject->setDataObject($itemMock));
    }

    public function testSetDataObjectSuccess()
    {
        $wrappingOptions = json_encode(['option' => 'wrapping_option']);
        $itemMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getGiftwrappingOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->expects($this->exactly(2))
            ->method('getGiftwrappingOptions')
            ->willReturn($wrappingOptions);
        $this->assertEquals($this->subject, $this->subject->setDataObject($itemMock));
    }

    public function testUpdateSuccess()
    {
        $wrappingOptions = json_encode(['option' => 'wrapping_option']);
        $itemMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getGiftwrappingOptions', 'setGiftwrappingOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->expects($this->exactly(2))
            ->method('getGiftwrappingOptions')
            ->willReturn($wrappingOptions);
        $this->assertEquals($this->subject, $this->subject->setDataObject($itemMock));

        $itemMock->expects($this->once())
            ->method('setGiftwrappingOptions')
            ->with($wrappingOptions)
            ->willReturn($wrappingOptions);
        $this->assertEquals($this->subject, $this->subject->update());
    }
}
