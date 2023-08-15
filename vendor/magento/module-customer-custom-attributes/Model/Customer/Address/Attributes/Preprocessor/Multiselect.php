<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer\Address\Attributes\Preprocessor;

use Magento\Framework\Api\AttributeValue;

/**
 * Preprocessor for multiselect type attributes
 */
class Multiselect extends AbstractPreprocessor
{
    /**
     * @inheritdoc
     */
    public function shouldBeProcessed(string $key, $attribute): bool
    {
        if ($key && is_array($attribute)) {
            return $this->checkAttributeFrontendInput($key, 'multiselect');
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function process(string $key, &$attribute)
    {
        $attribute = [AttributeValue::ATTRIBUTE_CODE => $key, AttributeValue::VALUE => $attribute];
    }

    /**
     * @inheritdoc
     */
    public function getAffectedAttributes(): array
    {
        return $this->getAttributesListByInputType('multiselect');
    }
}
