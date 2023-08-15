<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Account repository allows to check if email exists in Magento
 *
 * @api
 */
interface StorefrontAccountRepositoryInterface
{
    /**
     * Check if email exists in Storefront
     *
     * @param string $email
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function hasAccount(string $email): bool;
}
