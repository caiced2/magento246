<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Logging\Block\Adminhtml\Details;
use Magento\Logging\Model\Event;
use Magento\User\Model\UserFactory;
use PHPUnit\Framework\TestCase;

class DetailsTest extends TestCase
{
    /**
     * @var Details
     */
    protected $object;

    /**
     * @var Event
     */
    protected $eventMock;

    /**
     * @var Json
     */
    protected $jsonMock;

    protected function setUp(): void
    {
        $buttonListMock = $this->getMockBuilder(ButtonList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->method('getButtonList')
            ->willReturn($buttonListMock);
        $contextMock->method('getUrlBuilder')
            ->willReturn($urlBuilder);

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getInfo'])
            ->disableOriginalConstructor()
            ->getMock();

        $registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $registryMock->method('registry')
            ->willReturn($this->eventMock);

        $userFactory = $this->getMockBuilder(UserFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->object = $objectManager->getObject(
            Details::class,
            [
                'context' => $contextMock,
                'registry' => $registryMock,
                'userFactory' => $userFactory,
                'data' => [],
                'json' => $this->jsonMock
            ]
        );
    }

    public function testGetEventInfo()
    {
        $data = json_encode([
            "string" => "phrase",
            "number" => 42,
            "bool" => true,
            "collection" => [
                "fibo"=> [1, 2, 3, 5, 8]
            ]
        ]);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->eventMock->method('getInfo')
            ->willReturn($data);

        $this->assertNotEmpty($this->object->getEventInfo());
        $this->assertEquals(json_decode($data, true), $this->object->getEventInfo());
    }
}
