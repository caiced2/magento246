<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\Test\Unit\Model\Event;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Logging\Model\Event\Changes;
use PHPUnit\Framework\TestCase;

class ChangesTest extends TestCase
{
    /**
     * @var Changes
     */
    protected $object;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonMock;

    protected function setUp(): void
    {
        $eventManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->method('getEventDispatcher')
            ->willReturn($eventManagerMock);

        $registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock = $this->getMockBuilder(AbstractResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdFieldName', '_construct', 'getConnection'])
            ->getMock();
        $resourceCollectionMock = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->object = $objectManager->getObject(
            Changes::class,
            [
                'context' => $contextMock,
                'registry' => $registryMock,
                'resource' => $resourceMock,
                'resourceCollection' => $resourceCollectionMock,
                'skipFields' => [],
                'data' => [],
                'json' => $this->jsonMock
            ]
        );
    }

    /**
     * We are testing the method with data, where "Original Data" set and "Result Data" set are completely different.
     * First we set some data into the Object with Setters,
     * then call Method Under Test,
     * and then assert that it took the data that we set, converted into Json format and placed back
     */
    public function testBeforeSave()
    {
        $dataOriginal = [
            "name" => "Old Segment",
            "description" => "some",
            "is_active" => "0",
            "apply_to" => 0,
        ];

        $dataResult = [
            "name" => "New Segment",
            "description" => "",
            "is_active" => "1",
            "apply_to" => 1,
            "processing_frequency" => "1",
            "from_date" => null,
            "to_date" => null,
            "segment_id" => "4",
            "id" => "4"
        ];

        $this->jsonMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $this->object->setOriginalData($dataOriginal);
        $this->object->setResultData($dataResult);

        $this->object->beforeSave();

        $this->assertEquals(json_encode($dataOriginal), $this->object->getOriginalData());
        $this->assertEquals(json_encode($dataResult), $this->object->getResultData());
    }
}
