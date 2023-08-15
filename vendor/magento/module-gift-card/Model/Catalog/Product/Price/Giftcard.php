<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\Catalog\Product\Price;

class Giftcard extends \Magento\Catalog\Model\Product\Type\Price
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Cached amounts
     * @var array
     */
    protected $_amountCache = [];

    /**
     * Cached minimum and maximal amounts
     * @var array
     */
    protected $_minMaxCache = [];

    /**
     * Return price of the specified product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return float
     */
    public function getPrice($product)
    {
        if ($product->getData('price')) {
            return $product->getData('price');
        } elseif (!$product->getAllowOpenAmount()
            && (count($this->getAmounts($product)) === 1)
            && !$product->hasCustomOptions()
        ) {
            $amounts = $this->getAmounts($product);
            $amount = array_shift($amounts);
            return $amount['website_value'];
        } else {
            return 0;
        }
    }

    /**
     * Retrieve product final price
     *
     * @param int $qty
     * @param \Magento\Catalog\Model\Product $product
     * @return float
     */
    public function getFinalPrice($qty, $product)
    {
        $finalPrice = $product->getPrice();
        if ($product->hasCustomOptions()) {
            $customOption = $product->getCustomOption('giftcard_amount');
            if ($customOption) {
                $amounts = $product->getGiftcardAmounts();
                if (!empty($amounts) && count($amounts) === 1) {
                    $optionValue = $product->getGiftcardAmounts()[0]['value'];
                    if ($optionValue !== $customOption->getValue()) {
                        $finalPrice += $optionValue;
                    } else {
                        $finalPrice += $customOption->getValue();
                    }
                } else {
                    $finalPrice += $customOption->getValue();
                }
            }
        }
        $finalPrice = $this->_applyOptionsPrice($product, $qty, $finalPrice);

        $product->setData('final_price', $finalPrice);
        return max(0, $product->getData('final_price'));
    }

    /**
     * Load and set gift card amounts into product object
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getAmounts($product)
    {
        $prices = $product->getData('giftcard_amounts');

        if ($prices === null) {
            if ($attribute = $product->getResource()->getAttribute('giftcard_amounts')) {
                $attribute->getBackend()->afterLoad($product);
                $prices = $product->getData('giftcard_amounts');
            }
        }

        return $prices ? $prices : [];
    }

    /**
     * Return minimal amount for Giftcard product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return float
     */
    public function getMinAmount($product)
    {
        $minMax = $this->_calcMinMax($product);
        return $minMax['min'];
    }

    /**
     * Return maximal amount for Giftcard product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return float
     */
    public function getMaxAmount($product)
    {
        $minMax = $this->_calcMinMax($product);
        return $minMax['max'];
    }

    /**
     * Fill in $_amountCache or return precalculated sorted values for amounts
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getSortedAmounts($product)
    {
        if (!isset($this->_amountCache[$product->getId()])) {
            $result = [];

            $giftcardAmounts = $this->getAmounts($product);
            if (is_array($giftcardAmounts)) {
                foreach ($giftcardAmounts as $amount) {
                    $result[] = $this->priceCurrency->round($amount['website_value']);
                }
            }
            sort($result);
            $this->_amountCache[$product->getId()] = $result;
        }
        return $this->_amountCache[$product->getId()];
    }

    /**
     * Fill in $_minMaxCache or return precalculated values for min, max
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _calcMinMax($product)
    {
        if (!isset($this->_minMaxCache[$product->getId()])) {
            $min = $max = null;
            if ($product->getAllowOpenAmount()) {
                $openMin = $product->getOpenAmountMin();
                $openMax = $product->getOpenAmountMax();

                if ($openMin) {
                    $min = $openMin;
                } else {
                    $min = 0;
                }
                if ($openMax) {
                    $max = $openMax;
                } else {
                    $max = 0;
                }
            }

            foreach ($this->getSortedAmounts($product) as $amount) {
                if ($amount) {
                    if ($min === null) {
                        $min = $amount;
                    }
                    if ($max === null) {
                        $max = $amount;
                    }

                    $min = min($min, $amount);
                    if ($max != 0) {
                        $max = max($max, $amount);
                    }
                }
            }

            $this->_minMaxCache[$product->getId()] = ['min' => $min, 'max' => $max];
        }
        return $this->_minMaxCache[$product->getId()];
    }
}
