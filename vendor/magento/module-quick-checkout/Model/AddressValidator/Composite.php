<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\AddressValidator;

use Magento\QuickCheckout\Api\Data\AddressInterface;
use Magento\QuickCheckout\Model\AddressValidatorInterface;

/**
 * Composite address validator
 */
class Composite implements AddressValidatorInterface
{
    /**
     * @var AddressValidatorInterface[]
     */
    private $validators;

    /**
     * @param AddressValidatorInterface[] $validators
     */
    public function __construct(
        array $validators = []
    ) {
        foreach ($validators as $validator) {
            if (!($validator instanceof AddressValidatorInterface)) {
                throw new \InvalidArgumentException(
                    'Address validator must be instance of ' . AddressValidatorInterface::class . '.'
                );
            }
        }
        $this->validators = $validators;
    }

    /**
     * Validate the address
     *
     * @param AddressInterface $address
     * @return bool
     */
    public function validate(AddressInterface $address): bool
    {
        foreach ($this->validators as $validator) {
            if (!$validator->validate($address)) {
                return false;
            }
        }
        return true;
    }
}
