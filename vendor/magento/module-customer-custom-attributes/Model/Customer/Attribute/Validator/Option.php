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
use Magento\Framework\Serialize\Serializer\FormData;

/**
 * Attribute options validator.
 */
class Option implements ValidatorInterface
{
    /**
     * @var FormData
     */
    private $formDataSerializer;

    /**
     * @var array
     */
    private $multipleAttributeList;

    /**
     * @param FormData $formDataSerializer
     * @param array $multipleAttributeList
     */
    public function __construct(
        FormData $formDataSerializer,
        array $multipleAttributeList = []
    ) {
        $this->formDataSerializer = $formDataSerializer;
        $this->multipleAttributeList = $multipleAttributeList;
    }

    /**
     * @inheritdoc
     */
    public function validate(AttributeInterface $attribute): void
    {
        if ($attribute->getSerializedOptions() !== null) {
            try {
                $optionsData = $this->formDataSerializer->unserialize(
                    $attribute->getSerializedOptions()
                );
            } catch (\InvalidArgumentException $e) {
                throw new LocalizedException(
                    __("The attribute couldn't be validated due to an error. " .
                        "Verify your information and try again. If the error persists, please try again later.")
                );
            }

            $frontendInputType = $attribute->getData('frontend_input');
            $frontendInputType = (null === $frontendInputType) ? 'select' : $frontendInputType;

            if (isset($this->multipleAttributeList[$frontendInputType], $optionsData)) {
                $options = $optionsData[$this->multipleAttributeList[$frontendInputType]] ?? [];
                $this->checkUniqueOption($options);
                $valueOptions = $this->removeOptionsForDelete($options);
                $this->checkEmptyOption($valueOptions);
            }
        }
    }

    /**
     * Remove the data that was intended to be deleted.
     *
     * @param array $options
     * @return array
     */
    private function removeOptionsForDelete(array $options): array
    {
        $valueOptions = isset($options['value']) && is_array($options['value']) ? $options['value'] : [];
        foreach (array_keys($valueOptions) as $key) {
            if (!empty($options['delete'][$key])) {
                unset($valueOptions[$key]);
            }
        }

        return $valueOptions;
    }

    /**
     * Performs checking the uniqueness of the attribute options.
     *
     * @param array $options
     * @return void
     * @throws LocalizedException
     */
    private function checkUniqueOption(array $options = []): void
    {
        if (is_array($options)
            && isset($options['value'], $options['delete'])
            && !$this->isUniqueAdminValues($options['value'], $options['delete'])
        ) {
            throw new LocalizedException(__('The value of Admin must be unique.'));
        }
    }

    /**
     * Throws Exception if not unique values into options.
     *
     * @param array $optionsValues
     * @param array $deletedOptions
     * @return bool
     */
    private function isUniqueAdminValues(array $optionsValues, array $deletedOptions): bool
    {
        $adminValues = [];
        foreach ($optionsValues as $optionKey => $values) {
            if (!(isset($deletedOptions[$optionKey]) && $deletedOptions[$optionKey] === '1')) {
                $adminValues[] = reset($values);
            }
        }
        $uniqueValues = array_unique($adminValues);

        return $uniqueValues === $adminValues;
    }

    /**
     * Check that admin does not try to create option with empty admin scope option.
     *
     * @param array $optionsForCheck
     * @return void
     * @throws LocalizedException
     */
    private function checkEmptyOption(array $optionsForCheck = []): void
    {
        foreach ($optionsForCheck as $optionValues) {
            if (isset($optionValues[0]) && $optionValues[0] == '') {
                throw new LocalizedException(__('The value of Admin scope can\'t be empty.'));
            }
        }
    }
}
