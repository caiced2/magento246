<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator;

use Magento\CustomerCustomAttributes\Model\Customer\Attribute\ValidatorInterface;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;

/**
 * Attribute file extension validator.
 */
class FileExtension implements ValidatorInterface
{
    /**
     * @var NotProtectedExtension
     */
    private $extensionValidator;

    /**
     * @param NotProtectedExtension $extensionValidator
     */
    public function __construct(NotProtectedExtension $extensionValidator)
    {
        $this->extensionValidator = $extensionValidator;
    }

    /**
     * @inheritdoc
     */
    public function validate(AttributeInterface $attribute): void
    {
        if ($attribute->getData('frontend_input') === 'file') {
            $fileExtensions = explode(',', $attribute->getData('file_extensions'));
            $isForbiddenExtensionsExists = false;

            foreach ($fileExtensions as $fileExtension) {
                if (!$this->extensionValidator->isValid($fileExtension)) {
                    $isForbiddenExtensionsExists = true;
                }
            }

            if ($isForbiddenExtensionsExists) {
                throw new LocalizedException(__('Please correct the value for file extensions.'));
            }
        }
    }
}
