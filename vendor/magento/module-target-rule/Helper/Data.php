<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Helper;

/**
 * TargetRule data helper
 *
 * @api
 * @since 100.0.2
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_TARGETRULE_CONFIG = 'catalog/magento_targetrule/';

    const MAX_PRODUCT_LIST_RESULT = 20;

    /**
     * Retrieve Maximum Number of Products in Product List
     *
     * @param int $type product list type
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return int
     */
    public function getMaximumNumberOfProduct($type)
    {
        switch ($type) {
            case \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS:
                $number = $this->scopeConfig->getValue(
                    self::XML_PATH_TARGETRULE_CONFIG . 'related_position_limit',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                break;
            case \Magento\TargetRule\Model\Rule::UP_SELLS:
                $number = $this->scopeConfig->getValue(
                    self::XML_PATH_TARGETRULE_CONFIG . 'upsell_position_limit',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                break;
            case \Magento\TargetRule\Model\Rule::CROSS_SELLS:
                $number = $this->scopeConfig->getValue(
                    self::XML_PATH_TARGETRULE_CONFIG . 'crosssell_position_limit',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid product list type'));
        }

        return $this->getMaxProductsListResult($number);
    }

    /**
     * Show Related/Upsell/Cross-Sell Products behavior
     *
     * @param int $type
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return int
     */
    public function getShowProducts($type)
    {
        switch ($type) {
            case \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS:
                $show = $this->scopeConfig->getValue(
                    self::XML_PATH_TARGETRULE_CONFIG . 'related_position_behavior',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                break;
            case \Magento\TargetRule\Model\Rule::UP_SELLS:
                $show = $this->scopeConfig->getValue(
                    self::XML_PATH_TARGETRULE_CONFIG . 'upsell_position_behavior',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                break;
            case \Magento\TargetRule\Model\Rule::CROSS_SELLS:
                $show = $this->scopeConfig->getValue(
                    self::XML_PATH_TARGETRULE_CONFIG . 'crosssell_position_behavior',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid product list type'));
        }

        return $show;
    }

    /**
     * Retrieve maximum number of products can be displayed in product list
     *
     * If number is 0 (unlimited) or great global maximum return global maximum value
     *
     * @param int $number
     * @return int
     */
    public function getMaxProductsListResult($number = 0)
    {
        if ($number == 0 || $number > self::MAX_PRODUCT_LIST_RESULT) {
            $number = self::MAX_PRODUCT_LIST_RESULT;
        }

        return $number;
    }

    /**
     * Retrieve Rotation Mode in Product List
     *
     * @param int $type product list type
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return int
     */
    public function getRotationMode($type)
    {
        switch ($type) {
            case \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS:
                $mode = $this->scopeConfig->getValue(
                    self::XML_PATH_TARGETRULE_CONFIG . 'related_rotation_mode',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                break;
            case \Magento\TargetRule\Model\Rule::UP_SELLS:
                $mode = $this->scopeConfig->getValue(
                    self::XML_PATH_TARGETRULE_CONFIG . 'upsell_rotation_mode',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                break;
            case \Magento\TargetRule\Model\Rule::CROSS_SELLS:
                $mode = $this->scopeConfig->getValue(
                    self::XML_PATH_TARGETRULE_CONFIG . 'crosssell_rotation_mode',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid rotation mode type'));
        }
        return (int)$mode;
    }
}
