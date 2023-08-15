<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Plugin;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\ResourceModel\Collection\ExpiredQuotesCollection;

/**
 * Class CleanExpiredQuotesPlugin
 */
class CleanExpiredQuotesPlugin
{
    /**
     * Update subject with additional filter fields
     *
     * @param ExpiredQuotesCollection $subject
     * @param AbstractCollection $result
     * @return AbstractCollection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetExpiredQuotes(
        ExpiredQuotesCollection $subject,
        AbstractCollection $result
    ): AbstractCollection {
        $result->addFieldToFilter('is_persistent', 0);

        return $result;
    }
}
