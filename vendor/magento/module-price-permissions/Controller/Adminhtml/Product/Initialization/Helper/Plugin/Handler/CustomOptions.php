<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler;

use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper\HandlerInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;

class CustomOptions implements HandlerInterface
{
    /**
     * Handle Custom Options of Product
     *
     * @param Product $product
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function handle(Product $product)
    {
        $originalOptionsAssoc = [];
        $originalOptions = $product->getOptions();
        $options = $product->getData('product_options');
        if (!is_array($options)) {
            return;
        }

        if (is_array($originalOptions)) {
            foreach ($originalOptions as $originalOption) {
                /** @var $originalOption \Magento\Catalog\Model\Product\Option */
                $originalOptionAssoc = [];
                $originalOptionAssoc['id'] = $originalOption->getOptionId();
                $originalOptionAssoc['option_id'] = $originalOption->getOptionId();
                $originalOptionAssoc['type'] = $originalOption->getType();
                $originalOptionGroup = $originalOption->getGroupByType();
                if ($originalOptionGroup != ProductCustomOptionInterface::OPTION_GROUP_SELECT) {
                    $originalOptionAssoc['price'] = $originalOption->getPrice();
                    $originalOptionAssoc['price_type'] = $originalOption->getPriceType();
                } else {
                    $originalOptionAssoc['values'] = [];
                    foreach ($originalOption->getValues() as $value) {
                        /** @var $value \Magento\Catalog\Model\Product\Option\Value */
                        $originalOptionAssoc['values'][$value->getOptionTypeId()] = [
                            'price' => $value->getPrice(),
                            'price_type' => $value->getPriceType(),
                        ];
                    }
                }
                $originalOptionsAssoc[$originalOption->getOptionId()] = $originalOptionAssoc;
            }
        }

        foreach ($options as $optionId => &$option) {
            // For old options
            if (isset($originalOptionsAssoc[$optionId]) && $originalOptionsAssoc[$optionId]['type'] == $option['type']
            ) {
                if (!isset($option['values'])) {
                    $option['price'] = $originalOptionsAssoc[$optionId]['price'];
                    $option['price_type'] = $originalOptionsAssoc[$optionId]['price_type'];
                } elseif (is_array($option['values'])) {
                    foreach ($option['values'] as &$value) {
                        if (isset($originalOptionsAssoc[$optionId]['values'][$value['option_type_id']])) {
                            $originalValue = $originalOptionsAssoc[$optionId]['values'][$value['option_type_id']];
                            $value['price'] = $originalValue['price'];
                            $value['price_type'] = $originalValue['price_type'];
                        } else {
                            // Set zero price for new selections of old custom option
                            $value['price'] = '0';
                            $value['price_type'] = 0;
                        }
                    }
                }
            } else {
                // Set price to zero and price type to fixed for new options
                if (!isset($option['values'])) {
                    $option['price'] = '0';
                    $option['price_type'] = 0;
                } elseif (is_array($option['values'])) {
                    foreach ($option['values'] as &$value) {
                        $value['price'] = '0';
                        $value['price_type'] = 0;
                    }
                }
            }
        }

        $product->setData('product_options', $options);
    }
}
