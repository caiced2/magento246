<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Plugin;

use Magento\CustomerImportExport\Model\Import\Customer;

class ImportCustomer
{
    /**
     * Customer fields in file
     */
    protected $customerFields = [
        'reward_update_notification',
        'reward_warning_notification',
    ];

    /**
     * @param Customer $importCustomer
     * @param array $validColumnNames
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetValidColumnNames(Customer $importCustomer, array $validColumnNames)
    {
        return array_merge($validColumnNames, $this->customerFields);
    }
}
