<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Block\Adminhtml\Report\View\Tab\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Block\Adminhtml\Report\View\Tab\Grid\Column\Renderer\Text;
use Magento\Support\Model\Report\HtmlGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    /**
     * @var Text
     */
    protected $columnRenderer;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var HtmlGenerator|MockObject
     */
    protected $htmlGeneratorMock;

    /**
     * @var Column|MockObject
     */
    protected $columnMock;

    protected function setUp(): void
    {
        $this->htmlGeneratorMock = $this->getMockBuilder(HtmlGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->columnMock = $this->getMockBuilder(Column::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGetter'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->columnRenderer = $this->objectManagerHelper->getObject(
            Text::class,
            [
                'htmlGenerator' => $this->htmlGeneratorMock
            ]
        );
    }

    public function testGetValue()
    {
        $rawText = 'raw column text';
        $text = __($rawText);
        $row = new DataObject(['value' => $rawText]);
        $html = '<span class="cell-value-flag-yes">' . $text . '</span>';

        $this->columnRenderer->setColumn($this->columnMock);

        $this->columnMock->expects($this->any())
            ->method('getGetter')
            ->willReturn('getValue');
        $this->htmlGeneratorMock->expects($this->any())
            ->method('getGridCellHtml')
            ->with($text, $rawText)
            ->willReturn($html);

        $this->assertEquals($html, $this->columnRenderer->_getValue($row));
    }
}
