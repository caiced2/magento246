<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model;

use Magento\QuickCheckout\Api\Data\AddressInterface;

/**
 * Validates customer address
 */
interface AddressValidatorInterface
{

    /**
     * Validate the address
     *
     * @param AddressInterface $address
     * @return bool
     */
    public function validate(AddressInterface $address): bool;
}
