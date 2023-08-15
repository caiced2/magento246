<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomAttributeManagement\Block\Form\Renderer;

/**
 * EAV Entity Attribute Form Renderer Block for multiply select
 *
 * @api
 * @author      Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Multiselect extends \Magento\CustomAttributeManagement\Block\Form\Renderer\Select
{
    /**
     * Return array of select options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->getAttributeObject()->getSource()->getAllOptions();
    }

    /**
     * Returns original entity value (neither filtered nor escaped).
     *
     * @return string|null
     * @since 100.3.5
     */
    public function getValue(): ?string
    {
        $value = $this->getEntity()->getData($this->getAttributeObject()->getAttributeCode());

        if (is_array($value)) {
            $value = implode(',', $value);
        }

        return $value;
    }

    /**
     * Return array of values
     *
     * @return array
     */
    public function getValues()
    {
        $value = $this->getValue();
        if (!is_array($value)) {
            $value = explode(',', $value);
        }
        return $value;
    }

    /**
     * Check is value selected
     *
     * @param string $value
     * @return boolean
     */
    public function isValueSelected($value)
    {
        return in_array($value, $this->getValues());
    }
}
