<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report;

use Magento\Framework\Escaper;

/**
 * Html generator
 */
class HtmlGenerator
{
    /**
     * Report data limitation
     */
    const MAX_NONE_COLLAPSIBLE_CELL_STRING_LENGTH = 1000;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param Escaper $escaper
     */
    public function __construct(
        Escaper $escaper
    ) {
        $this->escaper = $escaper;
    }

    /**
     * Normalize, format and construct HTML table cell text for Magento grid block(s)
     *
     * @param \Magento\Framework\Phrase|string $text
     * @param string $rawText
     * @return string
     */
    public function getGridCellHtml($text, $rawText)
    {
        $text = $this->prepareCellHtml($text);
        $cellCss = $this->getCellClassByValue($rawText);
        return $cellCss !== null ? '<span class="cell-value-' . $cellCss . '">' . $text . '</span>' : $text;
    }

    /**
     * Normalize, format and construct HTML table cell text
     *
     * @param \Magento\Framework\Phrase|string $text
     * @param string $rawText
     * @param string $cellId
     * @return string
     */
    public function getExportTableCellHtml($text, $rawText, $cellId)
    {
        $text = $this->prepareCellHtml($text);
        $maxLength = self::MAX_NONE_COLLAPSIBLE_CELL_STRING_LENGTH;
        $isTextLengthMustBeCut = mb_strlen($rawText, 'UTF-8') > $maxLength;

        if ($isTextLengthMustBeCut) {
            $fullText = mb_substr($text, 0, $maxLength, 'UTF-8');
            $fullText .= '<a href="javascript:void(0)" onclick="showFullText(\'cell_' . $cellId . '\')"> ... More</a>';
            $fullText .='<div class="report-cell-text">' . $text . '</div>';
            $text = $fullText;
        }

        $cellCss = $this->getCellClassByValue($rawText);
        $html = '<td'. ($isTextLengthMustBeCut ? ' id="cell_' . $cellId . '"' : '')
            . ($cellCss !== null ? ' class="' . $cellCss . '"' : '') .'>'. $text . '</td>';
        return $html;
    }

    /**
     * Prepare HTML for text value of table cell
     *
     * @param \Magento\Framework\Phrase|string $text
     * @return string
     */
    protected function prepareCellHtml($text)
    {
        $text = $this->escaper->escapeHtml($text);
        $text = $this->replaceLeadingSpacesWithNoneBreakSpaces($text);
        $text = $this->prepareHtmlForFilePathStrings($text);
        $text = $this->prepareHtmlForDiffStrings($text);
        $text = str_replace(["\n", "\r"], '<br />', $text);
        return $text;
    }

    /**
     * Replace the leading spaces with &nbsp; with same number of times
     *
     * @param string $text
     * @return string
     */
    protected function replaceLeadingSpacesWithNoneBreakSpaces($text)
    {
        $originalLength = mb_strlen($text, 'UTF-8');
        $text = ltrim($text, ' ');
        $newLength = mb_strlen($text, 'UTF-8');
        return str_repeat('&nbsp;', $originalLength - $newLength) . $text;
    }

    /**
     * Replace special {text} constructions with styled HTML
     *
     * @param string $string
     * @return string|bool
     */
    protected function prepareHtmlForFilePathStrings($string)
    {
        $string = preg_replace('~\{\{([^}]+)\}\}~is', '[[[\1]]]', $string);
        $string = preg_replace('~\{([^{}]+)\}~is', '<span class="file-path">\1</span>', $string);
        return preg_replace('~\[\[\[([^]]+)\]\]\]~is', '{{\1}}', $string);
    }

    /**
     * Replace special (diff: +-<digit>) constructions with styled HTML
     *
     * @param string $string
     * @return string|bool
     */
    protected function prepareHtmlForDiffStrings($string)
    {
        $string = preg_replace('~\(diff: (\-[^\)]+)\)~is', '<span class="diff-negative">(\1)</span>', $string);
        return preg_replace('~\(diff: (\+[^\)]+)\)~is', '<span class="diff-positive">(\1)</span>', $string);
    }

    /**
     * Get table cell css class depending on its specific value
     *
     * Used in HTML format report
     *
     * @param mixed $value
     * @return null|string
     */
    protected function getCellClassByValue($value)
    {
        $yesValues = ['Yes', 'Enabled', 'Ready', 'Exists', 'success'];
        $processValues = ['Processing', 'Invalidated', 'running', 'pending', 'Scheduled'];
        $noValues = ['No', 'Disabled', 'Reindex Required', 'Missing', 'error'];

        $class = null;
        if ((in_array($value, $yesValues) && !empty($value))) {
            $class = 'flag-yes';
        } elseif (in_array($value, $processValues) && !empty($value)) {
            $class = 'flag-processing';
        } elseif (in_array($value, $noValues) && !empty($value)) {
            $class = 'flag-no';
        }
        return $class;
    }
}
