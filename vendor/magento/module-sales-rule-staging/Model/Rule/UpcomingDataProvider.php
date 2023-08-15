<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Model\Rule;

use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;

/**
 * Data provider for upcoming update form.
 */
class UpcomingDataProvider extends \Magento\SalesRule\Model\Rule\DataProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getMetadataValues()
    {
        return [];
    }
}
