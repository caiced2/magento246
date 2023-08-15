<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator;

use Magento\CustomerCustomAttributes\Model\Customer\Attribute\ValidatorInterface;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\StringLength;

/**
 * Attribute code length validator.
 */
class AttributeCodeLength implements ValidatorInterface
{
    /**
     * @var StringLength
     */
    private $stringLength;
    /**
     * @var array
     */
    private $codeLengthByEntityType;

    /**
     * @param StringLength $stringLength
     * @param array $codeLengthByEntityType
     */
    public function __construct(StringLength $stringLength, array $codeLengthByEntityType = [])
    {
        $this->stringLength = $stringLength;
        $this->codeLengthByEntityType = $codeLengthByEntityType;
    }

    /**
     * @inheritdoc
     */
    public function validate(AttributeInterface $attribute): void
    {
        $attributeId = $attribute->getId();

        if ($attributeId === null) {
            $attributeCodeMaxLength = $this->codeLengthByEntityType[$attribute->getEntityType()->getEntityTypeCode()]
                ?? Attribute::ATTRIBUTE_CODE_MAX_LENGTH;
            $this->stringLength->setMax($attributeCodeMaxLength);
            if (!$this->stringLength->isValid($attribute->getAttributeCode())) {
                throw new LocalizedException(
                    __(
                        'The attribute code needs to be %1 characters or fewer. Re-enter the code and try again.',
                        $attributeCodeMaxLength
                    )
                );
            }
        }
    }
}
