<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\Rma\Source;

/**
 * RMA Item attribute source abstract model
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
abstract class AbstractSource extends \Magento\Eav\Model\Entity\Attribute\Source\Table
{
    /**
     * Getter for all available options
     * @inheritdoc
     */
    public function getAllOptions($withLabels = true, $isMultiselect = false, $foregroundCountries = '')
    {
        $values = $this->_getAvailableValues();
        if ($withLabels) {
            $result = [];
            foreach ($values as $item) {
                $result[] = ['label' => $this->getItemLabel($item), 'value' => $item];
            }
            return $result;
        }
        return $values;
    }

    /**
     * Getter for all available options for filter in grid
     *
     * @return array
     */
    public function getAllOptionsForGrid()
    {
        $values = $this->_getAvailableValues();
        $result = [];
        foreach ($values as $item) {
            $result[$item] = $this->getItemLabel($item);
        }
        return $result;
    }

    /**
     * Get available keys for entities
     *
     * @return array
     */
    abstract protected function _getAvailableValues();

    /**
     * Get label based on the code
     *
     * @param string $item
     * @return string
     */
    abstract public function getItemLabel($item);
}
