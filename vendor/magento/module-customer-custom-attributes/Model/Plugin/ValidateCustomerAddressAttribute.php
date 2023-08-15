<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Plugin;

use Magento\Customer\Model\Attribute;
use Magento\CustomerCustomAttributes\Model\Customer\Attribute\CompositeValidator;

/**
 * Plugin for custom address attribute validation before save.
 */
class ValidateCustomerAddressAttribute
{
    /**
     * @var CompositeValidator
     */
    private $compositeValidator;

    /**
     * @param CompositeValidator $compositeValidator
     */
    public function __construct(CompositeValidator $compositeValidator)
    {
        $this->compositeValidator = $compositeValidator;
    }

    /**
     * Validate customer address attribute data before save.
     *
     * @param Attribute $subject
     * @return void
     */
    public function beforeBeforeSave(Attribute $subject)
    {
        $this->compositeValidator->validate($subject);
    }
}
