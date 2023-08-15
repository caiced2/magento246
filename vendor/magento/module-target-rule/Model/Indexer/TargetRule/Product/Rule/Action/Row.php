<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action;

/**
 * Class Row reindex action
 *
 * @package Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action
 */
class Row extends \Magento\TargetRule\Model\Indexer\TargetRule\AbstractAction
{
    /**
     * Execute Row reindex
     *
     * @param int|null $productId
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return void
     */
    public function execute($productId = null)
    {
        if (!isset($productId) || empty($productId)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We can\'t rebuild the index for an undefined product.')
            );
        }
        try {
            $this->_reindexByProductId($productId);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()), $e);
        }
    }
}
