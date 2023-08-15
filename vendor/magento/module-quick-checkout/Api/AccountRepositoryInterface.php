<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Api;

use Magento\QuickCheckout\Api\Data\AccountInterface;

/**
 * Account repository allows to check if email exists in Bolt and retrieve account information
 *
 * @api
 */
interface AccountRepositoryInterface
{
    /**
     * Check if email exists in Bolt
     *
     * @param string $email
     * @return bool
     */
    public function hasAccount(string $email): bool;

    /**
     * Retrieve account information
     *
     * @return \Magento\QuickCheckout\Api\Data\AccountInterface
     */
    public function getAccountDetails(): AccountInterface;
}
