<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action;

/**
 * Class Rows reindex action for mass actions
 *
 * @package Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action
 */
class Rows extends \Magento\TargetRule\Model\Indexer\TargetRule\AbstractAction
{
    /**
     * Execute Rows reindex
     *
     * @param array $productIds
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return void
     */
    public function execute($productIds)
    {
        if (empty($productIds)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not rebuild index for empty products array')
            );
        }
        try {
            $this->_reindexByProductIds($productIds);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()), $e);
        }
    }
}
