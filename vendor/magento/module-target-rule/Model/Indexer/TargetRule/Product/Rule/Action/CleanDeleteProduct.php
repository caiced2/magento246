<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action;

/**
 * Class Clean deleted product action
 *
 * @package Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action
 */
class CleanDeleteProduct extends \Magento\TargetRule\Model\Indexer\TargetRule\AbstractAction
{
    /**
     * Remove deleted product from index
     *
     * @param int $productId
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return void
     */
    public function execute($productId)
    {
        if (!isset($productId) || empty($productId)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We can\'t rebuild the index for an undefined product.')
            );
        }
        try {
            $this->_deleteProductFromIndex($productId);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()), $e);
        }
    }
}
