<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report;

/**
 * Class responsible for converting report data to end format
 */
class DataConverter
{
    /**#@+
     * Report data limitations
     */
    const MAX_ROW_AMOUNT = 1000;
    const MAX_COLUMN_AMOUNT = 20;
    const MAX_COLUMN_LENGTH = 120;
    /**#@-*/

    /**#@-*/
    protected $headers = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $columnSizes = [];

    /**
     * @var int
     */
    protected $columnsAmount = 0;

    /**
     * @var int
     */
    protected $singleRowColumnsAmount = 0;

    /**
     * @var bool
     */
    protected $multipleRowMode = false;

    /**
     * @var bool
     */
    protected $dataLimitExceeded = false;

    /**
     * Get prepared system report data for output in CLI or HTML format
     *
     * @param array $data contains array "headers" with headers and array "data" with data
     * @return array
     * @throws \Magento\Framework\Exception\StateException
     */
    public function prepareData(array $data)
    {
        $headers = is_array($data['headers']) ? $data['headers'] : [];
        $data = is_array($data['data']) ? $data['data'] : [];

        $this->columnSizes = [];
        $this->columnsAmount = 0;
        $this->singleRowColumnsAmount = 0;
        $this->prepareTableHeader($headers);
        $this->prepareTableData($data);
        $this->normalizeTableHeader();

        /**
         * Data array normalization
         * Save maximum column sizes
         */
        if ($this->singleRowColumnsAmount > 0) {
            $this->normalizeTableDataInSingleRowMode();
        } else {
            $this->normalizeTableData();
        }

        /**
         * Normalization of maximum column sizes
         */
        foreach ($this->columnSizes as &$size) {
            if ($size > self::MAX_COLUMN_LENGTH) {
                $size = self::MAX_COLUMN_LENGTH;
            }
        }

        return [
            'column_sizes' => $this->columnSizes,
            'headers' => $this->headers,
            'data' => $this->data,
            'count' => sizeof($this->data)
        ];
    }

    /**
     * Prepare headers if applicable and update columns sizes
     *
     * @param array $data
     * @return void
     */
    protected function prepareTableHeader(array $data)
    {
        $colNum = 0;
        $this->headers = [];

        foreach ($data as $key => $column) {
            $colNum++;
            $trimmedColumn = trim($column);

            // If maximum column number limit reached then set last column as "And more..." string
            if ($colNum == self::MAX_COLUMN_AMOUNT + 1) {
                $column = __('And more...');
            } elseif ($column !== null && !is_bool($column) && empty($trimmedColumn)) {
                // If column data is not empty string then use it as column title
                // Otherwise "Column N" string will be used as title
                $column = __('Column %1', $colNum);
            }

            $column = $this->prepareColumnData($column);

            //Update column sizes
            $length = mb_strlen($column, 'UTF-8');
            $this->updateColumnSizes($colNum - 1, $length);

            $this->headers[$key] = $column;

            // If maximum column number limit reached then stop further row data processing
            if ($colNum == self::MAX_COLUMN_AMOUNT + 1) {
                break;
            }
        }

        if ($this->columnsAmount < $colNum) {
            $this->columnsAmount = $colNum;
        }
    }

    /**
     * Prepare table body data and update columns sizes
     *
     * @param array $data
     * @return void
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function prepareTableData(array $data)
    {
        $this->data = [];
        $this->multipleRowMode = false;
        $this->dataLimitExceeded = false;
        $dataRowsCount = sizeof($data);

        /**
         * Data validation and preparation
         * Collect column data sizes
         */
        for ($rowIndex = 0; $rowIndex < $dataRowsCount; $rowIndex++) {
            $origRow = $data[$rowIndex];
            if (is_array($origRow)) {
                $this->data[$rowIndex] = $this->prepareRowData($origRow, $rowIndex);
            } else {
                $this->data[0][] = $this->prepareRowDataInSingleRowMode($origRow, $rowIndex);
            }

            // If maximum row count limit reached then stop further data processing
            if ($this->dataLimitExceeded) {
                break;
            }
        }

        // If data was retrieved as single row then make sure that maximum column number detection has last update
        if ($this->singleRowColumnsAmount > $this->columnsAmount) {
            $this->columnsAmount = $this->singleRowColumnsAmount;
        }
    }

    /**
     * Prepare report table row data
     *
     * @param array $data
     * @param int $rowIndex
     * @return array
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function prepareRowData(array $data, $rowIndex)
    {
        $newRow = [];
        $colNum = 0;

        if ($this->singleRowColumnsAmount > 0) {
            throw new \Magento\Framework\Exception\StateException(
                __('Preparing system report data: Detected Single Row Mode but data may be incomplete.')
            );
        }

        $this->multipleRowMode = true;

        // If maximum row count limit reached then set last row as "And more..." string
        if ($rowIndex == self::MAX_ROW_AMOUNT) {
            $data = __('And more...');
            $this->dataLimitExceeded = true;
        }

        foreach ($data as $key => $column) {
            $colNum++;
            // If maximum column number limit reached then set last column as "And more..." string
            if ($colNum == self::MAX_COLUMN_AMOUNT + 1) {
                $column = __('And more...');
            }

            $column = $this->prepareColumnData($column);
            $newRow[$key] = $column;

            // Detect maximum column data length, take into account multi line columns that will be split
            $maxLength = $this->getMaxLineLength($column);
            $this->updateColumnSizes($colNum - 1, $maxLength);

            // If maximum column number limit reached then stop further row data processing
            if ($colNum == self::MAX_COLUMN_AMOUNT + 1) {
                break;
            }
        }

        // Detect maximum column number in one row
        if ($colNum > $this->columnsAmount) {
            $this->columnsAmount = $colNum;
        }
        return $newRow;
    }

    /**
     * Prepare report table row data for single row mode
     *
     * @param string $data
     * @param int $rowIndex
     * @return string
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function prepareRowDataInSingleRowMode($data, $rowIndex)
    {
        if ($this->multipleRowMode) {
            throw new \Magento\Framework\Exception\StateException(
                __('Preparing system report data: Detected Single Row Mode but data may be incomplete.')
            );
        }

        if ($rowIndex == self::MAX_COLUMN_AMOUNT) {
            $data = __('And more...');
            $this->dataLimitExceeded = true;
        }

        $column = $this->prepareColumnData($data);

        // Detect maximum column data length
        $maxLength = mb_strlen($column, 'UTF-8');
        $this->updateColumnSizes($this->singleRowColumnsAmount, $maxLength);

        $this->singleRowColumnsAmount++;
        return $column;
    }

    /**
     * Normalize headers data
     *
     * @return void
     */
    protected function normalizeTableHeader()
    {
        $headersColCount = sizeof($this->headers);
        $colNum = $headersColCount + 1;
        if ($headersColCount < $this->columnsAmount) {
            for ($counter = 0; $counter < $this->columnsAmount - $headersColCount; $counter++) {
                $column = __('Column %1', $colNum);
                $this->headers[] = $column;
                $maxLength = mb_strlen($column, 'UTF-8');
                $this->updateColumnSizes($colNum - 1, $maxLength);
                $colNum++;
            }
        }
    }

    /**
     * Normalize table body data
     *
     * @return void
     */
    protected function normalizeTableData()
    {
        foreach ($this->data as &$row) {
            $dataRowColCount = sizeof($row);
            $colNum = $dataRowColCount + 1;
            if ($dataRowColCount < $this->columnsAmount) {
                for ($counter = 0; $counter < $this->columnsAmount - $dataRowColCount; $counter++) {
                    $column = '';
                    $row[] = $column;

                    // Only if headers wasn't normalized/specified
                    if (empty($this->headers)) {
                        // Detect maximum column data length
                        $maxLength = mb_strlen($column, 'UTF-8');
                        if (isset($this->columnSizes[$colNum - 1]) && $this->columnSizes[$colNum - 1] < $maxLength
                        ) {
                            $this->columnSizes[$colNum - 1] = $maxLength;
                        }
                    }
                    $colNum++;
                }
            }
        }
        unset($row);
    }

    /**
     * Normalize table body data in single row mode
     *
     * @return void
     */
    protected function normalizeTableDataInSingleRowMode()
    {
        if ($this->singleRowColumnsAmount < $this->columnsAmount) {
            $colNum = $this->singleRowColumnsAmount + 1;
            for ($counter = 0; $counter < $this->columnsAmount - $this->singleRowColumnsAmount; $counter++) {
                $column = '';
                $this->data[0][] = $column;

                // Only if headers wasn't normalized/specified
                if (empty($this->headers)) {
                    $this->columnSizes[$colNum - 1] = mb_strlen($column, 'UTF-8');
                }
                $colNum++;
            }
        }
    }

    /**
     * Get maximum line length value in a string with multiple lines
     *
     * @param string $string
     * @return int
     */
    protected function getMaxLineLength($string)
    {
        $maxLength = 0;
        $pregSplit = preg_split("~[\n\r]+~", $string);
        foreach ($pregSplit as $line) {
            $length = mb_strlen($line, 'UTF-8');
            if ($length > $maxLength) {
                $maxLength = $length;
            }
        }
        return $maxLength;
    }

    /**
     * Update column size value at specific index position
     *
     * @param int $index
     * @param int $length
     * @return void
     */
    protected function updateColumnSizes($index, $length)
    {
        if (!isset($this->columnSizes[$index]) || $this->columnSizes[$index] < $length) {
            $this->columnSizes[$index] = $length;
        }
    }

    /**
     * Convert table column data into readable format
     *
     * @param mixed $data
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function prepareColumnData($data)
    {
        if (!($data instanceof \Magento\Framework\Phrase)) {
            if ($data === null) {
                $data = 'null';
            } elseif (is_bool($data)) {
                $data = $data ? 'true' : 'false';
            } elseif (is_object($data)) {
                $data = 'Object ' . get_class($data);
            } elseif (is_array($data)) {
                $data = 'array(' . sizeof($data) . ')';
            } elseif (is_string($data)) {
                if (empty($data)) {
                    $data = '';
                }
            } elseif (is_numeric($data)) {
                // as is
            } else {
                $data = (string)$data;
            }
        }

        return $data;
    }
}
