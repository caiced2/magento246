<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\Test\Unit\Block\Adminhtml\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Logging\Block\Adminhtml\Grid\Renderer\Details;
use PHPUnit\Framework\TestCase;

class DetailsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Details
     */
    protected $object;

    /**
     * @var Json
     */
    protected $jsonMock;

    protected function setUp(): void
    {
        $escaper = $this->createMock(Escaper::class);
        $escaper->expects($this->any())->method('escapeHtml')->willReturnArgument(0);
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->method('getEscaper')
            ->willReturn($escaper);

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->object = $this->objectManager->getObject(
            Details::class,
            [
                'context' => $contextMock,
                'data' => [],
                'json' => $this->jsonMock
            ]
        );
    }

    /**
     * @param array $data
     * @param string $expectedResult
     * @dataProvider renderDataProvider
     */
    public function testRender($data, $expectedResult)
    {
        $row = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $row->method('getData')->willReturn($data);
        $column = $this->getMockBuilder(Column::class)
            ->setMethods(['getIndex'])
            ->disableOriginalConstructor()
            ->getMock();
        $column->method('getIndex')->willReturn($row);

        $this->object->setColumn($column);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->assertEquals($expectedResult, $this->object->render($row));
    }

    public function renderDataProvider()
    {
        return [
            'set1' => [
                'true',
                'true',
            ],
            'set2' => [
                '{"general": ["some parsed value"]}',
                'some parsed value',
            ]
        ];
    }
}
