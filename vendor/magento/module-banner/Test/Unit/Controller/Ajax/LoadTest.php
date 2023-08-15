<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Controller\Ajax;

use Magento\Backend\App\Action\Context;
use Magento\Banner\Controller\Ajax\Load;
use Magento\Banner\Model\Banner\Data;
use Magento\Banner\Model\Banner\DataFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoadTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Load
     */
    protected $object;

    /**
     * @var Context
     */
    protected $contextMock;

    /**
     * @var Http|MockObject
     */
    protected $requestMock;

    /**
     * @var JsonFactory|MockObject
     */
    protected $jsonFactoryMock;

    /**
     * @var RawFactory|MockObject
     */
    protected $rawFactoryMock;

    /**
     * @var DataFactory|MockObject
     */
    protected $dataFactoryMock;

    /**
     * @var Json|MockObject
     */
    protected $jsonMock;

    /**
     * @var Raw|MockObject
     */
    protected $rawMock;

    /**
     * @var Data|MockObject
     */
    protected $dataMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rawFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\RawFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock = $this->getMockBuilder(\Magento\Banner\Model\Banner\DataFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSectionData'])
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->setMethods(['setData', 'setStatusHeader'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rawMock = $this->getMockBuilder(Raw::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getObjectManager', 'getRequest'])
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->dataMock);
        $this->jsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->jsonMock);

        $this->object = $this->objectManager->getObject(
            Load::class,
            [
                'context' => $this->contextMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'rawFactory' => $this->rawFactoryMock,
                'dataFactory' => $this->dataFactoryMock,
            ]
        );
    }

    /**
     * Test if the method is excuted or not
     *
     * @param array $sectionData
     * @param array $expectedResult
     * @param string $expectedJson
     * @dataProvider getDataProvider
     * @throws NotFoundException
     */
    public function testExecute(array $sectionData, array $expectedResult, $expectedJson)
    {
        $this->requestMock
            ->expects($this->any())
            ->method('getMethod')
            ->willReturn('GET');
        $this->requestMock
            ->expects($this->any())
            ->method('isXmlHttpRequest')
            ->willReturn(true);
        $this->dataMock
            ->expects($this->once())
            ->method('getSectionData')
            ->willReturn($sectionData);
        $this->jsonMock
            ->expects($this->once())
            ->method('setData')
            ->with($expectedResult)
            ->willReturn($expectedJson);

        $this->assertSame($expectedJson, $this->object->execute());
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return [
            [
                $sectionData = [],
                ['data' => $sectionData],
                '{"data":[]}'
            ],
            [
                $sectionData = [
                    'items' => [
                        'salesrule' => [],
                        'catalogrule' => [],
                        'fixed' => [
                            1 => [
                                'content' => 'Test',
                                'types' => [],
                                'id' => 1,
                            ],
                        ],
                    ],
                ],
                ['data' => $sectionData],
                '{"data":{"items":{"salesrule":[],"catalogrule":[],'
                . '"fixed":{"1":{"content":"Test","types":[],"id":1}}}}}'
            ],
        ];
    }

    /**
     * Test if the method is valid ajax request or not
     *
     * @return void
     * @throws NotFoundException
     */
    public function testNonAjaxRequest()
    {
        $this->requestMock
            ->expects($this->any())
            ->method('getMethod')
            ->willReturn('GET');
        $this->requestMock
            ->expects($this->any())
            ->method('isXmlHttpRequest')
            ->willReturn(false);
        $this->dataMock
            ->expects($this->any())
            ->method('getSectionData')
            ->willReturn([]);
        $this->jsonMock
            ->expects($this->any())
            ->method('setData')
            ->with(['message' => __('Invalid Request')])
            ->willReturn(__('Invalid Request'));
        $this->assertEquals(__('Invalid Request'), $this->object->execute());
    }
}
