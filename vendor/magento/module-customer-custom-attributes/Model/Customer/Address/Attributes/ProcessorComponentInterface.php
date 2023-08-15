<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer\Address\Attributes;

use Magento\Framework\Api\AttributeInterface;

/**
 * Attribute processor.
 *
 * A component of the CustomerAddressCustomAttributesProcessor composition
 */
interface ProcessorComponentInterface
{
    /**
     * Process attribute object.
     *
     * @param AttributeInterface $attribute
     * @return void
     */
    public function process(AttributeInterface $attribute): void;
}
