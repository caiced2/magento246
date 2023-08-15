<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardImportExport\Model\Export;

use Magento\CatalogImportExport\Model\Export\RowCustomizerInterface;
use Magento\ImportExport\Model\Import;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

/**
 * Prepare data and columns to export.
 */
class RowCustomizer implements RowCustomizerInterface
{
    /**
     * Product's giftcard prefix.
     */
    const GIFTCARD_PREFIX = 'giftcard_';

    /**
     * Product's giftcard config prefix.
     */
    const USE_CONFIG_PREFIX = 'use_config_';

    /**
     * Product's giftcard data.
     *
     * @var array
     */
    private $giftcardData = [];

    /**
     * Product's giftcard columns.
     *
     * @var array
     */
    private $giftCardColumns = [
        'giftcard_type',
        'giftcard_allow_open_amount',
        'giftcard_open_amount_min',
        'giftcard_open_amount_max',
        'giftcard_amount',
    ];

    /**
     * Product's giftcard additional columns.
     *
     * @var array
     */
    private $additionalColumns = [
        'is_redeemable',
        'lifetime',
        'allow_message',
        'email_template',
    ];

    /**
     * Prepare data for export.
     *
     * @param mixed $collection
     * @param int[] $productIds
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function prepareData($collection, $productIds)
    {
        $productCollection = clone $collection;
        $productCollection->addAttributeToFilter(
            'entity_id',
            ['in' => $productIds]
        )->addAttributeToFilter(
            'type_id',
            ['eq' => 'giftcard']
        );

        return $this->populateGiftCardData($productCollection);
    }

    /**
     * Populate giftcard product data.
     *
     * @param Collection $collection
     * @return $this
     */
    private function populateGiftCardData(Collection $collection)
    {
        foreach ($collection as $product) {
            $id = (int)$product->getEntityId();
            $this->giftcardData[$id]['giftcard_type'] = $product->getGiftcardType();
            $this->giftcardData[$id]['giftcard_allow_open_amount'] = $product->getAllowOpenAmount();
            $this->giftcardData[$id]['giftcard_open_amount_min'] = $product->getOpenAmountMin();
            $this->giftcardData[$id]['giftcard_open_amount_max'] = $product->getOpenAmountMax();
            $this->giftcardData[$id]['giftcard_amount'] = $this->getGiftCardAmount($product);
            $this->getAdditionalColumnsData($id, $product);
        }

        return $this;
    }

    /**
     * Get additional columns data.
     *
     * @param int $id
     * @param Product $product
     * @return $this
     */
    private function getAdditionalColumnsData(int $id, Product $product)
    {
        foreach ($this->additionalColumns as $column) {
            $configData = self::USE_CONFIG_PREFIX . $column;
            $this->giftcardData[$id][$configData] = $product->getData($configData);
            $data = $product->getData($column);
            if (!$product->getData($configData) && isset($data)) {
                $this->giftcardData[$id][self::GIFTCARD_PREFIX . $column] = $data;
            }
        }

        return $this;
    }

    /**
     * Get giftcard amount.
     *
     * @param Product $product
     * @return string
     */
    private function getGiftCardAmount(Product $product): string
    {
        $values = [];
        $amounts = $product->getData('giftcard_amounts');
        foreach ($amounts as $item) {
            $values[] = $item['value'];
        }

        return implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $values);
    }

    /**
     * Set headers columns.
     *
     * @param array $columns
     * @return mixed
     */
    public function addHeaderColumns($columns)
    {
        $giftCardColumns = $this->prepareColumns();
        $columns = array_merge($columns, $giftCardColumns);

        return $columns;
    }

    /**
     * Prepare and merge all columns.
     *
     * @return array
     */
    private function prepareColumns(): array
    {
        $additionalColumns = [];
        foreach ($this->additionalColumns as $column) {
            $additionalColumns[] = self::USE_CONFIG_PREFIX . $column;
            $additionalColumns[] = self::GIFTCARD_PREFIX . $column;
        }
        $giftCardColumns = array_merge($this->giftCardColumns, $additionalColumns);

        return $giftCardColumns;
    }

    /**
     * Add data for export.
     *
     * @param array $dataRow
     * @param int $productId
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addData($dataRow, $productId)
    {
        if (!empty($this->giftcardData[$productId])) {
            $dataRow = array_merge($dataRow, $this->giftcardData[$productId]);
        }

        return $dataRow;
    }

    /**
     * Calculate the largest links block.
     *
     * @param array $additionalRowsCount
     * @param int $productId
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAdditionalRowsCount($additionalRowsCount, $productId)
    {
        return $additionalRowsCount;
    }
}
