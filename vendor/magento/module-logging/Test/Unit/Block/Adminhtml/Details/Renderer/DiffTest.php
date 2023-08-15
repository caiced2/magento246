<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\Test\Unit\Block\Adminhtml\Details\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Extended;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Logging\Block\Adminhtml\Details\Renderer\Diff;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DiffTest extends TestCase
{
    /**
     * @var Diff
     */
    protected $_object;

    /**
     * @var MockObject
     */
    protected $_column;

    /**
     * @var MockObject
     */
    protected $json;

    protected function setUp(): void
    {
        $escaper = $this->createMock(Escaper::class);
        $escaper->expects($this->any())->method('escapeHtml')->willReturnArgument(0);
        $context = $this->createMock(Context::class);
        $context->expects($this->once())
            ->method('getEscaper')
            ->willReturn($escaper);

        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_column = $this->getMockBuilder(Extended::class)
            ->addMethods(['getValues', 'getIndex', 'getHtmlName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->_object = new Diff($context, [], $this->json);
        $this->_object->setColumn($this->_column);
    }

    /**
     * @param array $rowData
     * @param string $expectedResult
     * @dataProvider renderDataProvider
     */
    public function testRender($rowData, $expectedResult)
    {
        $this->json->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->_column->expects($this->once())->method('getIndex')->willReturn('result_data');
        $this->assertStringContainsString($expectedResult, $this->_object->render(new DataObject($rowData)));
    }

    public function renderDataProvider()
    {
        return [
            'allowed' => [
                ['result_data' => '{"allow":["TMM","USD"]}'],
                '<dd class="value">TMM</dd><dd class="value">USD</dd>',
            ],
            'time' => [
                ['result_data' => '{"time":["00","00","00"]}'],
                '<dd class="value">00:00:00</dd>',
            ]
        ];
    }
}
