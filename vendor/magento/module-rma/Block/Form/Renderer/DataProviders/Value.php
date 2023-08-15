<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Block\Form\Renderer\DataProviders;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Eav\Model\Attribute;

/**
 * Provides default value if value is empty
 */
class Value implements ArgumentInterface
{
    /**
     * Return default value if no value provided
     *
     * @param Attribute $attribute
     * @param string|null $value
     * @return string|null
     */
    public function getSelectedValue(
        Attribute $attribute,
        ?string $value
    ) : ?string {
        if (empty($value)) {
            $value = $attribute->getDefaultValue();
        }
        return $value;
    }
}
