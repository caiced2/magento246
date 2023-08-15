<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer\Attribute;

use Magento\Eav\Model\Entity\Attribute\AttributeInterface;

/**
 * Customer attributes composite validator.
 */
class CompositeValidator implements ValidatorInterface
{
    /**
     * @var ValidatorInterface[]
     */
    private $validators;

    /**
     * @param array $validators
     */
    public function __construct(array $validators = [])
    {
        $this->validators = $validators;
    }

    /**
     * @inheritdoc
     */
    public function validate(AttributeInterface $attribute): void
    {
        foreach ($this->validators as $validator) {
            $validator->validate($attribute);
        }
    }
}
