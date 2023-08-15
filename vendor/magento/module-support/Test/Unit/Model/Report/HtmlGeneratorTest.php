<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report;

use Magento\Framework\Escaper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Report\HtmlGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\Support\Model\Report\HtmlGenerator
 */
class HtmlGeneratorTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var string
     */
    protected $nonParsed = 'non-parsed';

    /**
     * @var string
     */
    protected $parsed = 'parsed';

    /**
     * @var string
     */
    protected $diffNegative = 'diff-negative';

    /**
     * @var string
     */
    protected $diffPositive = 'diff-positive';

    /**
     * @var string
     */
    protected $text;

    /**
     * @var string
     */
    protected $result;

    /**
     * @var HtmlGenerator
     */
    protected $generator;

    protected function setUp(): void
    {
        $this->text = " <text>{{" . $this->nonParsed . "}}{" . $this->parsed . "}(diff: -" . $this->diffNegative
            . ")(diff: +" . $this->diffPositive . ")</text>\n\r";

        $this->result = "&nbsp;&lt;text&gt;{{" . $this->nonParsed . "}}"
            . "<span class=\"file-path\">" . $this->parsed . "</span>"
            . "<span class=\"diff-negative\">(-" . $this->diffNegative . ")</span>"
            . "<span class=\"diff-positive\">(+" . $this->diffPositive . ")</span>"
            . "&lt;/text&gt;<br /><br />";
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $escaper = $this->objectManagerHelper->getObject(
            Escaper::class
        );
        /** @var HtmlGenerator $generator */
        $this->generator = $this->objectManagerHelper->getObject(
            HtmlGenerator::class,
            [
                'escaper' => $escaper
            ]
        );
    }

    /**
     * @param string $rawText
     * @param string $cellCss
     * @dataProvider getGridCellHtmlDataProvider
     */
    public function testGetGridCellHtml($rawText, $cellCss)
    {
        $expected = "<span class=\"cell-value-flag-" . $cellCss . "\">" . $this->result . "</span>";
        $this->assertEquals($expected, $this->generator->getGridCellHtml($this->text, $rawText));
    }

    /**
     * @param string $rawText
     * @param string $cellCss
     * @dataProvider getGridCellHtmlDataProvider
     */
    public function testGetExportTableCellHtml($rawText, $cellCss)
    {
        $expected = "<td class=\"flag-" . $cellCss . "\">" . $this->result . "</td>";
        $this->assertEquals($expected, $this->generator->getExportTableCellHtml($this->text, $rawText, 1));
    }

    public function testGetExportTableCellHtmlTextLengthMustBeCut()
    {
        $cellId = 1;
        $rawText = str_repeat(
            'a',
            HtmlGenerator::MAX_NONE_COLLAPSIBLE_CELL_STRING_LENGTH + 1
        );

        $expected = "<td id=\"cell_" . $cellId . "\">"
            . $this->result
            . "<a href=\"javascript:void(0)\" onclick=\"showFullText('cell_" . $cellId . "')\"> ... More</a>"
            . "<div class=\"report-cell-text\">" . $this->result . "</div>"
            . "</td>";

        $this->assertEquals($expected, $this->generator->getExportTableCellHtml($this->text, $rawText, $cellId));
    }

    /**
     * @return array
     */
    public function getGridCellHtmlDataProvider()
    {
        return [
            ['Yes', 'yes'],
            ['Enabled', 'yes'],
            ['Ready', 'yes'],
            ['Exists', 'yes'],
            ['success', 'yes'],
            ['Processing', 'processing'],
            ['Invalidated', 'processing'],
            ['running', 'processing'],
            ['pending', 'processing'],
            ['Scheduled', 'processing'],
            ['No', 'no'],
            ['Disabled', 'no'],
            ['Reindex Required', 'no'],
            ['Missing', 'no'],
            ['error', 'no'],
        ];
    }
}
